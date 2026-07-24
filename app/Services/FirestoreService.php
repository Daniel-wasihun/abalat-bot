<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FirestoreService
{
    protected ?FirestoreClient $firestore = null;
    protected bool $isFallbackMode = false;
    protected string $storagePath;

    public function __construct()
    {
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS_PATH', 'storage/app/firebase-credentials.json'));
        $projectId       = env('FIREBASE_PROJECT_ID', 'telegram-feedback-demo');
        $this->storagePath = storage_path('app/firestore_data');

        if (File::exists($credentialsPath) && filesize($credentialsPath) > 10) {
            try {
                $this->firestore = new FirestoreClient([
                    'projectId'   => $projectId,
                    'keyFilePath' => $credentialsPath,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Firestore init failed, falling back to local JSON store: ' . $e->getMessage());
                $this->isFallbackMode = true;
            }
        } else {
            $this->isFallbackMode = true;
        }

        if ($this->isFallbackMode) {
            File::ensureDirectoryExists($this->storagePath, 0755);
        }
    }

    public function isFallback(): bool
    {
        return $this->isFallbackMode;
    }

    public function collection(string $collectionName): FirestoreCollectionReference
    {
        return new FirestoreCollectionReference(
            $this->firestore,
            $collectionName,
            $this->isFallbackMode,
            $this->storagePath
        );
    }
}

/* ─────────────────────────────────────────────────────────── */

class FirestoreCollectionReference
{
    protected ?FirestoreClient $firestore;
    protected string $collection;
    protected bool   $isFallback;
    protected string $storagePath;

    // Per-collection cache TTL in seconds
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        ?FirestoreClient $firestore,
        string $collection,
        bool $isFallback,
        string $storagePath
    ) {
        $this->firestore   = $firestore;
        $this->collection  = $collection;
        $this->isFallback  = $isFallback;
        $this->storagePath = $storagePath;
    }

    /* ── Cache helpers ─────────────────────────────────────── */

    private function collectionCacheKey(): string
    {
        return "fs_col_{$this->collection}";
    }

    /**
     * Invalidate the collection cache without touching unrelated caches.
     * Also clears the dashboard widget cache since it aggregates multiple collections.
     */
    private function bustCache(): void
    {
        Cache::forget($this->collectionCacheKey());
        Cache::forget('dashboard_data');
    }

    /* ── Local JSON helpers ────────────────────────────────── */

    protected function getFilePath(): string
    {
        return $this->storagePath . '/' . $this->collection . '.json';
    }

    protected function readLocalData(): array
    {
        $path = $this->getFilePath();
        if (!File::exists($path)) {
            return [];
        }
        return json_decode(File::get($path), true) ?: [];
    }

    protected function writeLocalData(array $data): void
    {
        File::put($this->getFilePath(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /* ── Public API ────────────────────────────────────────── */

    public function doc(string $id): FirestoreDocumentReference
    {
        return new FirestoreDocumentReference(
            $this->firestore,
            $this->collection,
            $id,
            $this->isFallback,
            $this->storagePath
        );
    }

    public function add(array $data): array
    {
        $id  = (string) Str::uuid();
        $now = now()->toIso8601String();

        $data = array_merge($data, [
            'id'        => $id,
            'createdAt' => $data['createdAt'] ?? $now,
            'updatedAt' => $now,
        ]);

        if (!$this->isFallback && $this->firestore) {
            try {
                $this->firestore->collection($this->collection)->document($id)->set($data);
            } catch (\Throwable $e) {
                \Log::error("Firestore add error [{$this->collection}]: " . $e->getMessage());
            }
        }

        // Always write locally (dual-write / fallback)
        $items       = $this->readLocalData();
        $items[$id]  = $data;
        $this->writeLocalData($items);

        $this->bustCache();

        return ['id' => $id, 'data' => $data];
    }

    /**
     * Fetch all documents with 5-minute in-memory + Laravel cache.
     * Cache is collection-scoped so updates to one collection don't bust others.
     */
    public function get(): array
    {
        return Cache::remember($this->collectionCacheKey(), self::CACHE_TTL, function () {
            if (!$this->isFallback && $this->firestore) {
                try {
                    $snapshot = $this->firestore->collection($this->collection)->documents();
                    $results  = [];
                    foreach ($snapshot as $doc) {
                        if ($doc->exists()) {
                            $item       = $doc->data();
                            $item['id'] = $doc->id();
                            $results[]  = $item;
                        }
                    }
                    return $results;
                } catch (\Throwable $e) {
                    \Log::error("Firestore get error [{$this->collection}]: " . $e->getMessage());
                }
            }

            return array_values($this->readLocalData());
        });
    }

    /**
     * In-memory where filter on top of the cached get().
     */
    public function where(string $field, string $op, mixed $value): array
    {
        $all = $this->get();

        return array_values(array_filter($all, function ($item) use ($field, $op, $value) {
            $val = $item[$field] ?? null;
            return match ($op) {
                '=', '==' => $val == $value,
                '!='      => $val != $value,
                '>'       => $val >  $value,
                '>='      => $val >= $value,
                '<'       => $val <  $value,
                '<='      => $val <= $value,
                'in'      => is_array($value) && in_array($val, $value),
                default   => $val == $value,
            };
        }));
    }
}

/* ─────────────────────────────────────────────────────────── */

class FirestoreDocumentReference
{
    protected ?FirestoreClient $firestore;
    protected string $collection;
    protected string $id;
    protected bool   $isFallback;
    protected string $storagePath;

    public function __construct(
        ?FirestoreClient $firestore,
        string $collection,
        string $id,
        bool $isFallback,
        string $storagePath
    ) {
        $this->firestore   = $firestore;
        $this->collection  = $collection;
        $this->id          = $id;
        $this->isFallback  = $isFallback;
        $this->storagePath = $storagePath;
    }

    private function collectionCacheKey(): string
    {
        return "fs_col_{$this->collection}";
    }

    private function bustCache(): void
    {
        Cache::forget($this->collectionCacheKey());
        Cache::forget('dashboard_data');
    }

    protected function getFilePath(): string
    {
        return $this->storagePath . '/' . $this->collection . '.json';
    }

    protected function readLocalData(): array
    {
        $path = $this->getFilePath();
        if (!File::exists($path)) {
            return [];
        }
        return json_decode(File::get($path), true) ?: [];
    }

    protected function writeLocalData(array $data): void
    {
        File::put($this->getFilePath(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function get(): ?array
    {
        if (!$this->isFallback && $this->firestore) {
            try {
                $doc = $this->firestore->collection($this->collection)->document($this->id)->snapshot();
                if ($doc->exists()) {
                    $data       = $doc->data();
                    $data['id'] = $doc->id();
                    return $data;
                }
                return null;
            } catch (\Throwable $e) {
                \Log::error("Firestore doc get error [{$this->collection}/{$this->id}]: " . $e->getMessage());
            }
        }

        return $this->readLocalData()[$this->id] ?? null;
    }

    public function set(array $data, bool $merge = true): void
    {
        $now        = now()->toIso8601String();
        $data['id'] = $this->id;
        $data['updatedAt'] = $now;

        if (!$this->isFallback && $this->firestore) {
            try {
                $this->firestore->collection($this->collection)
                    ->document($this->id)
                    ->set($data, ['merge' => $merge]);
            } catch (\Throwable $e) {
                \Log::error("Firestore doc set error [{$this->collection}/{$this->id}]: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        if ($merge && isset($items[$this->id])) {
            $items[$this->id] = array_merge($items[$this->id], $data);
        } else {
            $data['createdAt'] = $data['createdAt'] ?? $now;
            $items[$this->id]  = $data;
        }
        $this->writeLocalData($items);
        $this->bustCache();
    }

    public function update(array $data): void
    {
        $data['updatedAt'] = now()->toIso8601String();

        if (!$this->isFallback && $this->firestore) {
            try {
                $updateData = [];
                foreach ($data as $k => $v) {
                    $updateData[] = ['path' => $k, 'value' => $v];
                }
                $this->firestore->collection($this->collection)
                    ->document($this->id)
                    ->update($updateData);
            } catch (\Throwable $e) {
                \Log::error("Firestore doc update error [{$this->collection}/{$this->id}]: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        if (isset($items[$this->id])) {
            $items[$this->id] = array_merge($items[$this->id], $data);
            $this->writeLocalData($items);
        }
        $this->bustCache();
    }

    public function delete(): void
    {
        if (!$this->isFallback && $this->firestore) {
            try {
                $this->firestore->collection($this->collection)->document($this->id)->delete();
            } catch (\Throwable $e) {
                \Log::error("Firestore doc delete error [{$this->collection}/{$this->id}]: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        if (isset($items[$this->id])) {
            unset($items[$this->id]);
            $this->writeLocalData($items);
        }
        $this->bustCache();
    }
}
