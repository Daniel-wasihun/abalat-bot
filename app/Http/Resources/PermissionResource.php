<?php

namespace App\Http\Resources;

class PermissionResource extends ApiResource {
    public static $wrap = 'permission';

    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name__localized,
            'slug' => $this->slug,
            'module' => $this->module,
            'action' => $this->action,
            'description' => $this->description__localized,
            'is_system_level' => (bool) $this->is_system_level,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
