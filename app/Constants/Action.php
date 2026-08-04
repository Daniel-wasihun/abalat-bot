<?php

namespace App\Constants;

class Action {
    public const VIEW    = 'view';
    public const CREATE  = 'create';
    public const EDIT    = 'edit';
    public const DELETE  = 'delete';
    public const MANAGE  = 'manage';
    public const NOTIFY  = 'notify';
    public const EXPORT  = 'export';

    /**
     * Get all action values.
     */
    public static function all(): array {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::MANAGE,
            self::NOTIFY,
            self::EXPORT,
        ];
    }

    /**
     * Get action labels mapped by their values.
     */
    public static function labelMap(): array {
        $map = [];
        foreach (self::all() as $value) {
            $map[$value] = \App\Services\BackLang::translate("action.{$value}");
        }
        return $map;
    }
}
