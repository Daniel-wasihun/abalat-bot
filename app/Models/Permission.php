<?php

namespace App\Models;

use App\Constants\Module;
use App\Constants\Action;
use App\Traits\Localizable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model {
    use SoftDeletes, HasSlug;
    protected $fillable = ['name', 'slug', 'module', 'action', 'description', 'is_system_level', 'is_active'];

    protected $casts = [
        'is_system_level' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Generate slug from module and action
     */
    protected function generateSlug(): void {
        if ($this->module && $this->action) {
            $this->slug = strtolower($this->module . '.' . $this->action);
        }
    }

    protected static function boot() {
        parent::boot();

        static::saving(function ($permission) {
            $moduleValid = in_array($permission->module, \App\Constants\Module::all());
            $actionValid = in_array($permission->action, \App\Constants\Action::all());

            if (!$moduleValid || !$actionValid) {
                throw new \Exception("Invalid module ({$permission->module}) or action ({$permission->action})");
            }

            // Force slug generation
            $permission->generateSlug();

            $translations = \App\Services\FrontLang::getTranslations('en');
            $actionLabel = $translations["action.{$permission->action}"] ?? ucfirst($permission->action);
                $moduleLabel = $translations["module.{$permission->module}"] ?? ucfirst($permission->module);
                $raw = "{$actionLabel} {$moduleLabel}";

                // Force ONLY first letter capital, rest lower
                $permission->name = mb_strtoupper(mb_substr($raw, 0, 1, "UTF-8"), "UTF-8") .
                    mb_strtolower(mb_substr($raw, 1, null, "UTF-8"), "UTF-8");
        });
    }


    public function roles(): BelongsToMany {
        return $this->belongsToMany(Role::class);
    }
}
