<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
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
        $projectId = env('FIREBASE_PROJECT_ID', 'telegram-feedback-demo');
        $this->storagePath = storage_path('app/firestore_data');

        if (File::exists($credentialsPath) && filesize($credentialsPath) > 10) {
            try {
                $this->firestore = new FirestoreClient([
                    'projectId' => $projectId,
                    'keyFilePath' => $credentialsPath,
                ]);
            } catch (\Throwable $e) {
                \Log::warning("Firestore init failed, falling back to local JSON store: " . $e->getMessage());
                $this->isFallbackMode = true;
            }
        } else {
            $this->isFallbackMode = true;
        }

        if ($this->isFallbackMode) {
            if (!File::exists($this->storagePath)) {
                File::makeDirectory($this->storagePath, 0755, true);
            }
        }
    }

    public function isFallback(): bool
    {
        return $this->isFallbackMode;
    }

    public function collection(string $collectionName)
    {
        return new FirestoreCollectionReference($this->firestore, $collectionName, $this->isFallbackMode, $this->storagePath);
    }
}

class FirestoreCollectionReference
{
    protected ?FirestoreClient $firestore;
    protected string $collection;
    protected bool $isFallback;
    protected string $storagePath;

    public function __construct(?FirestoreClient $firestore, string $collection, bool $isFallback, string $storagePath)
    {
        $this->firestore = $firestore;
        $this->collection = $collection;
        $this->isFallback = $isFallback;
        $this->storagePath = $storagePath;
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
        $content = File::get($path);
        return json_decode($content, true) ?: [];
    }

    protected function writeLocalData(array $data): void
    {
        File::put($this->getFilePath(), json_encode($data, JSON_PRETTY_PRINT));
    }

    public function doc(string $id)
    {
        return new FirestoreDocumentReference($this->firestore, $this->collection, $id, $this->isFallback, $this->storagePath);
    }

    public function add(array $data): array
    {
        $id = (string) Str::uuid();
        $data['id'] = $id;
        $now = now()->toIso8601String();
        if (!isset($data['createdAt'])) {
            $data['createdAt'] = $now;
        }
        $data['updatedAt'] = $now;

        if (!$this->isFallback && $this->firestore) {
            try {
                $this->firestore->collection($this->collection)->document($id)->set($data);
                return ['id' => $id, 'data' => $data];
            } catch (\Throwable $e) {
                \Log::error("Firestore add error: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        $items[$id] = $data;
        $this->writeLocalData($items);

        return ['id' => $id, 'data' => $data];
    }

    public function get(): array
    {
        if (!$this->isFallback && $this->firestore) {
            try {
                $snapshot = $this->firestore->collection($this->collection)->documents();
                $results = [];
                foreach ($snapshot as $doc) {
                    if ($doc->exists()) {
                        $item = $doc->data();
                        $item['id'] = $doc->id();
                        $results[] = $item;
                    }
                }
                return $results;
            } catch (\Throwable $e) {
                \Log::error("Firestore get error: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        return array_values($items);
    }

    public function where(string $field, string $op, $value): array
    {
        $all = $this->get();
        return array_values(array_filter($all, function ($item) use ($field, $op, $value) {
            $val = $item[$field] ?? null;
            switch ($op) {
                case '=':
                case '==':
                    return $val == $value;
                case '!=':
                    return $val != $value;
                case '>':
                    return $val > $value;
                case '>=':
                    return $val >= $value;
                case '<':
                    return $val < $value;
                case '<=':
                    return $val <= $value;
                case 'in':
                    return is_array($value) && in_array($val, $value);
                default:
                    return $val == $value;
            }
        }));
    }
}

class FirestoreDocumentReference
{
    protected ?FirestoreClient $firestore;
    protected string $collection;
    protected string $id;
    protected bool $isFallback;
    protected string $storagePath;

    public function __construct(?FirestoreClient $firestore, string $collection, string $id, bool $isFallback, string $storagePath)
    {
        $this->firestore = $firestore;
        $this->collection = $collection;
        $this->id = $id;
        $this->isFallback = $isFallback;
        $this->storagePath = $storagePath;
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
        File::put($this->getFilePath(), json_encode($data, JSON_PRETTY_PRINT));
    }

    public function get(): ?array
    {
        if (!$this->isFallback && $this->firestore) {
            try {
                $doc = $this->firestore->collection($this->collection)->document($this->id)->snapshot();
                if ($doc->exists()) {
                    $data = $doc->data();
                    $data['id'] = $doc->id();
                    return $data;
                }
                return null;
            } catch (\Throwable $e) {
                \Log::error("Firestore doc get error: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        return $items[$this->id] ?? null;
    }

    public function set(array $data, bool $merge = true): void
    {
        $data['id'] = $this->id;
        $now = now()->toIso8601String();
        $data['updatedAt'] = $now;

        if (!$this->isFallback && $this->firestore) {
            try {
                $this->firestore->collection($this->collection)->document($this->id)->set($data, ['merge' => $merge]);
            } catch (\Throwable $e) {
                \Log::error("Firestore doc set error: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        if ($merge && isset($items[$this->id])) {
            $items[$this->id] = array_merge($items[$this->id], $data);
        } else {
            if (!isset($data['createdAt'])) {
                $data['createdAt'] = $now;
            }
            $items[$this->id] = $data;
        }
        $this->writeLocalData($items);
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
                $this->firestore->collection($this->collection)->document($this->id)->update($updateData);
            } catch (\Throwable $e) {
                \Log::error("Firestore doc update error: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        if (isset($items[$this->id])) {
            $items[$this->id] = array_merge($items[$this->id], $data);
            $this->writeLocalData($items);
        }
    }

    public function delete(): void
    {
        if (!$this->isFallback && $this->firestore) {
            try {
                $this->firestore->collection($this->collection)->document($this->id)->delete();
            } catch (\Throwable $e) {
                \Log::error("Firestore doc delete error: " . $e->getMessage());
            }
        }

        $items = $this->readLocalData();
        if (isset($items[$this->id])) {
            unset($items[$this->id]);
            $this->writeLocalData($items);
        }
    }
}
