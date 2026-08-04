<?php

namespace App\Common\Lang;

abstract class Lang {
    protected static string $key;
    protected static string $name;
    protected static string $icon = 'globe.png'; // default fallback

    abstract public static function translations(): array;

    public static function key(): string {
        return static::$key;
    }

    public static function name(): string {
        return static::$name;
    }

    public static function icon(): string {
        return static::$icon;
    }

    public static function info(): array {
        return [
            'key'   => static::key(),
            'name'  => static::name(),
            'icon'  => static::icon(),
        ];
    }

    public static function all(): array {
        return array_merge(static::info(), [
            'translations' => static::translations(),
        ]);
    }
}
