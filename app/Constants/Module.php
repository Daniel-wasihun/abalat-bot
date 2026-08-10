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
    public const ACADEMIC_COURSES = 'academic_courses';
    public const ACADEMIC_CLASSES = 'academic_classes';

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
            self::ACADEMIC_COURSES,
            self::ACADEMIC_CLASSES,
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
