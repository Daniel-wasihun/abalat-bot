<?php

namespace App\Helpers;

use App\Constants\Module;

/**
 * Fluent helper for generating middleware-based permission strings.
 * Example: PermissionHelper::books()->create() => 'permission:books.create'
 */
class Permission
{
    /**
     * Generate a raw permission middleware string.
     */
    public static function make(string $module, string $action): string
    {
        return "permission:{$module}.{$action}";
    }

    /**
     * Books module permissions.
     */
    public static function books(): PermissionGenerator
    {
        return new PermissionGenerator(Module::BOOKS);
    }

    /**
     * Users module permissions.
     */
    public static function users(): PermissionGenerator
    {
        return new PermissionGenerator(Module::USERS);
    }

    /**
     * Roles module permissions.
     */
    public static function roles(): PermissionGenerator
    {
        return new PermissionGenerator(Module::ROLES);
    }

    /**
     * Permissions module permissions.
     */
    public static function permissions(): PermissionGenerator
    {
        return new PermissionGenerator(Module::PERMISSIONS);
    }

    /**
     * Permissions module permissions.
     */
    public static function categories(): PermissionGenerator
    {
        return new PermissionGenerator(Module::CATEGORIES);
    }

    /**
     * Campuses module permissions.
     */
    public static function campuses(): PermissionGenerator
    {
        return new PermissionGenerator(Module::CAMPUSES);
    }

    /**
     * Colleges module permissions.
     */
    public static function colleges(): PermissionGenerator
    {
        return new PermissionGenerator(Module::COLLEGES);
    }

    /**
     * Schools module permissions.
     */
    public static function schools(): PermissionGenerator
    {
        return new PermissionGenerator(Module::SCHOOLS);
    }

    /**
     * Departments module permissions.
     */
    public static function departments(): PermissionGenerator
    {
        return new PermissionGenerator(Module::DEPARTMENTS);
    }

    /**
     * Courses module permissions.
     */
    public static function courses(): PermissionGenerator
    {
        return new PermissionGenerator(Module::COURSES);
    }

    /**
     * Course Outlines module permissions.
     */
    public static function courseOutlines(): PermissionGenerator
    {
        return new PermissionGenerator(Module::COURSE_OUTLINES);
    }

    /**
     * Lecture Notes module permissions.
     */
    public static function lectureNotes(): PermissionGenerator
    {
        return new PermissionGenerator(Module::LECTURE_NOTES);
    }

    /**
     * Video Lectures module permissions.
     */
    public static function videoLectures(): PermissionGenerator
    {
        return new PermissionGenerator(Module::VIDEO_LECTURES);
    }

    /**
     * Reference Books module permissions.
     */
    public static function referenceBooks(): PermissionGenerator
    {
        return new PermissionGenerator(Module::REFERENCE_BOOKS);
    }

    /**
     * Assignments module permissions.
     */
    public static function assignments(): PermissionGenerator
    {
        return new PermissionGenerator(Module::ASSIGNMENTS);
    }

    /**
     * Worksheets module permissions.
     */
    public static function worksheets(): PermissionGenerator
    {
        return new PermissionGenerator(Module::WORKSHEETS);
    }


    /**
     * Dynamic module support for any other modules.
     */
    public static function __callStatic($name, $arguments)
    {
        // Try to handle module names from the enum if possible
        return new PermissionGenerator($name);
    }
}
