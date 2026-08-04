<?php

namespace App\Repositories\Postgres;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;

class PostgresSettingRepository implements SettingRepositoryInterface
{
    public function get(string $key, $default = null)
    {
        $setting = Setting::find($key);
        return $setting ? $setting->value : $default;
    }

    public function set(string $key, $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function getAll(): array
    {
        return Setting::pluck('value', 'key')->toArray();
    }
}
