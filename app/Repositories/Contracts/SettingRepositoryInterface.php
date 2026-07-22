<?php

namespace App\Repositories\Contracts;

interface SettingRepositoryInterface
{
    public function get(string $key, $default = null);
    public function set(string $key, $value): void;
    public function getAll(): array;
}
