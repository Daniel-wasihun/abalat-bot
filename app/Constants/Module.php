<?php

namespace App\Constants;

class Module
{
    public const DASHBOARD = 'dashboard';
    public const USERS = 'users';
    public const ROLES = 'roles';
    public const PERMISSIONS = 'permissions';
    public const SECURITY = 'security';
    public const BOT = 'bot';

    /**
     * Get all module values.
     */
    public static function all(): array
    {
        return [
            self::DASHBOARD,
            self::USERS,
            self::ROLES,
            self::PERMISSIONS,
            self::SECURITY,
            self::BOT,
        ];
    }

    /**
     * Get module labels mapped by their values.
     */
    public static function labelMap(): array
    {
        $map = [];
        foreach (self::all() as $value) {
            $map[$value] = \App\Services\BackLang::translate("module.{$value}");
        }
        return $map;
    }
}
