<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug {
    /**
     * Boot the trait to generate slugs automatically.
     */
    public static function bootHasSlug() {
        static::creating(function ($model) {
            $slugSourceField = $model->getSlugSourceField();
            $sourceValue = $model->getAttribute($slugSourceField);

            // Handle localized fields (jsonb)
            if (is_array($sourceValue)) {
                $sourceValue = $sourceValue['en'] ?? array_values($sourceValue)[0] ?? 'slug';
            }

            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($sourceValue);
            }
        });

        static::updating(function ($model) {
            $slugSourceField = $model->getSlugSourceField();
            if ($model->isDirty($slugSourceField)) {
                $sourceValue = $model->getAttribute($slugSourceField);

                // Handle localized fields (jsonb)
                if (is_array($sourceValue)) {
                    $sourceValue = $sourceValue['en'] ?? array_values($sourceValue)[0] ?? 'slug';
                }

                $model->slug = static::generateUniqueSlug($sourceValue);
            }
        });
    }

    /**
     * Generate a unique slug.
     */
    public static function generateUniqueSlug(string $title): string {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)
            ->when(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class)), function($query) {
                return $query->withTrashed();
            })
            ->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Get the field to use as the slug source.
     * Override this in the model if needed.
     */
    protected function getSlugSourceField(): string {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'name';
    }

    /**
     * Use slug as route key.
     */
    /**
     * Use slug as route key.
     */
    public function getRouteKeyName() {
        return 'slug';
    }

    /**
     * Resolve route binding specifically for slugs, with ID fallback.
     */
    public function resolveRouteBinding($value, $field = null) {
        $query = $this->where(function($q) use ($value, $field) {
            $q->where($field ?? 'slug', $value);
            if (is_numeric($value)) {
                $q->orWhere('id', $value);
            }
        });

        // Handle soft deletes if trait is used
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            $query->whereNull('deleted_at');
        }

        return $query->firstOrFail();
    }
}
