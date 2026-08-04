<?php

namespace App\Services;

class BackMessage {
    /**
     * Get the translation for the given key from the backend translations.
     *
     * @param string $key
     * @param array $replacements
     * @return string
     */
    public static function get(string $key, array $replacements = []): string {
        return BackLang::translate($key, $replacements);
    }

    /**
     * Set the application locale.
     *
     * @param string $locale
     * @return void
     */
    public static function set(string $locale): void {
        app()->setLocale($locale);
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public static function current(): string {
        return app()->getLocale();
    }
}
