<?php

namespace App\Helpers;




/**
 * Generator for individual permissions within a module.
 */

class PermissionGenerator {
    public function __construct(protected string $module) {
    }

    public function view(): string {
        return Permission::make($this->module, 'view');
    }

    public function create(): string {
        return Permission::make($this->module, 'create');
    }

    public function edit(): string {
        return Permission::make($this->module, 'edit');
    }

    public function delete(): string {
        return Permission::make($this->module, 'delete');
    }

    /**
     * Custom actions like 'assign', 'revoke', etc.
     */
    public function __call(string $name, array $arguments): string {
        return Permission::make($this->module, $name);
    }

    /**
     * Return the module name if string cast needed.
     */
    public function __toString(): string {
        return $this->module;
    }
}
