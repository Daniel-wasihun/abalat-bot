<?php

namespace App\Constants;

class Type {
    public const STUDENT = 'student';
    public const TEACHER = 'teacher';
    public const STAFF = 'staff';

    /**
     * Get all user type values.
     */
    public static function all(): array {
        return [
            self::STUDENT,
            self::TEACHER,
            self::STAFF,
        ];
    }

    /**
     * Get user type labels mapped by their values.
     */
    public static function labelMap(): array {
        $map = [];
        foreach (self::all() as $value) {
            $map[$value] = \App\Services\BackLang::translate("attendance.user_types.{$value}");
        }
        return $map;
    }
}
