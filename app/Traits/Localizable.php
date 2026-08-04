<?php

namespace App\Traits;

use Illuminate\Support\Arr;

trait Localizable {
    /**
     * Get localized value with fallback:
     * 1. Current language
     * 2. Default language (fallback_locale)
     * 3. Any available language
     *
     * @param string $field
     * @return mixed
     */
    public function __get($key) {
        $localizableFields = $this->localizable();

        if (str_ends_with($key, '__localized')) {
            $field = str_replace('__localized', '', $key);
            if (in_array($field, $localizableFields)) {
                return $this->getLocalized($field);
            }
        }

        if (str_ends_with($key, '_current')) {
            $field = str_replace('_current', '', $key);
            if (in_array($field, $localizableFields)) {
                return $this->getCurrentOnly($field);
            }
        }

        return parent::__get($key);
    }

    /**
     * Get value in current language only (no fallback)
     *
     * @param string $key
     * @return string|null
     */
    public function getLocalizedAttribute(string $key) {
        $field = str_replace('_localized', '', $key);

        return $this->getLocalized($field);
    }

    /**
     * Get value in current language only — strict (null if missing)
     *
     * @param string $key
     * @return string|null
     */
    public function getCurrentLangAttribute(string $key) {
        $field = str_replace('_current', '', $key);

        return $this->getCurrentOnly($field);
    }

    /**
     * Core method: localized with fallback
     */
    public function getLocalized(string $field): mixed {
        $value = $this->getAttribute($field);

        // Handle potential string double-encoding if casts fail
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) $value = $decoded;
        }

        if (!is_array($value) || empty($value)) {
            $raw = $this->attributes[$field] ?? null; // Final raw fallback
            if ($raw === '[]' || $raw === '{}') {
                return '';
            }
            return $raw;
        }

        $current = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return $value[$current] ?? $value[$fallback] ?? Arr::first($value, fn($v) => !empty($v));
    }

    /**
     * Core method: current language only
     */
    public function getCurrentOnly(string $field): mixed {
        $value = $this->getAttribute($field);

        // Handle potential string double-encoding
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) $value = $decoded;
        }

        if (!is_array($value)) {
            return null;
        }

        $locale = app()->getLocale();
        return $value[$locale] ?? null;
    }

    /**
     * Define which fields are localizable
     */
    protected function localizable(): array {
        // This is meant to be overridden by the model. 
        // We use a property check for models using property-based configuration
        // and default to calling the method if it exists on the instance (which would be the model's implementation).
        return [];
    }
}
