<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends ApiResource {
    public static $wrap = 'role';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name__localized,
            'slug' => $this->slug,
            'description' => $this->description__localized,
            'permissions' => $this->permissions->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name__localized,
                'slug' => $p->slug,
            ])->toArray(),
            'users_count' => $this->users()->count(),
            'hierarchy_level' => $this->hierarchy_level,
            'is_system_level' => (bool) $this->is_system_level,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
