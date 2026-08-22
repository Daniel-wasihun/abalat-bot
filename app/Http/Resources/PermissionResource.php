<?php

namespace App\Http\Resources;

class PermissionResource extends ApiResource {
    public static $wrap = 'permission';

    public function toArray($request): array {
        // Generate localized names dynamically based on action and module
        $langs = ['en', 'am', 'om'];
        $nameLocalized = [];
        foreach ($langs as $l) {
            $t = \App\Services\FrontLang::getTranslations($l);
            $actionLabel = $t["action.{$this->action}"] ?? ucfirst($this->action);
            $moduleLabel = $t["module.{$this->module}"] ?? ucfirst($this->module);
            
            if ($l === 'am' || $l === 'om') {
                $raw = "{$moduleLabel} {$actionLabel}";
            } else {
                $raw = "{$actionLabel} {$moduleLabel}";
            }
            
            // Capitalize first letter
            $nameLocalized[$l] = mb_strtoupper(mb_substr($raw, 0, 1, "UTF-8"), "UTF-8") . mb_strtolower(mb_substr($raw, 1, null, "UTF-8"), "UTF-8");
        }

        return [
            'id' => $this->id,
            'name' => $nameLocalized,
            'slug' => $this->slug,
            'module' => $this->module,
            'action' => $this->action,
            'description' => $this->description__localized ?? $this->description ?? "",
            'is_system_level' => (bool) $this->is_system_level,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
