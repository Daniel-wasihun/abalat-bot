<?php

namespace App\Services;

use App\Translation\Back\English;
use App\Translation\Back\Amharic;
use App\Translation\Back\Oromiffa;
use Illuminate\Support\Facades\App;

class BackLang {
    protected static array $languages = [
        'en' => English::class,
        'am' => Amharic::class,
        'om' => Oromiffa::class,
    ];

    public static function getAvailableLangKeys(): array {
        return array_keys(static::$languages);
    }

    public static function getDefaultLanguage(): string {
        return 'en';
    }

    public static function getTranslations(string $lang): array {
        $lang = strtolower(substr(trim($lang), 0, 2));
        $class = static::$languages[$lang] ?? static::$languages[static::getDefaultLanguage()];
        return $class::translations();
    }

    public static function getLanguageList(): array {
        return array_values(array_map(fn($class) => $class::info(), static::$languages));
    }

    public static function translate(string $key, array $replacements = []): string {
        $locale = App::getLocale();
        $translations = static::getTranslations($locale);
        $message = $translations[$key] ?? $key;

        foreach ($replacements as $placeholder => $value) {
            $message = str_replace(':' . $placeholder, $value, $message);
        }

        return $message;
    }
}
