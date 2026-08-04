<?php

namespace App\Services;

use App\Translation\Front\English;
use App\Translation\Front\Amharic;
use App\Translation\Front\Oromiffa;

class FrontLang {
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
}
