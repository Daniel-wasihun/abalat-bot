<?php

namespace App\Traits;

use App\Helpers\Response;
use Illuminate\Support\Facades\Auth;

trait HasOwnership {
    /**
     * Check if the authenticated user has ownership of the resource
     * or has administrative bypass permissions.
     *
     * @param mixed $resource
     * @return bool
     */
    protected function checkOwnership($resource): bool {
        $user = Auth::user();
        if (!$user) return false;

        // 1. Super Admin Bypass
        // Super admins have global bypass for all resources.
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 2. Admin Bypass with Module Authorization
        // Only users with Admin roles can bypass individual ownership.
        // They must also have the relevant module permission to perform the action.
        if ($user->hasRole(['admin', 'system_admin'])) {
            $module = $this->getModuleFromResource($resource);
            if (!$module || $user->hasPermission("{$module}.edit") || $user->hasPermission("{$module}.delete")) {
                return true;
            }
        }

        // 3. Ownership Check (Staff, Managers, Teachers, etc.)
        // Non-admin users can ONLY modify resources they personally uploaded,
        // even if they have module-level 'edit' or 'delete' permissions.
        return $resource->uploaded_by === $user->id;
    }

    /**
     * Returns a professional 403 Forbidden response for ownership violations.
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ownershipError($message = null) {
        $message = $message ?? __('auth.ownership_error');
        return Response::_403($message);
    }

    /**
     * Scopes a query to only include resources the user is authorized to manage.
     */
    protected function scopeOwnership($query) {
        $user = Auth::user();
        if (!$user) return $query->whereRaw('1 = 0');

        // 1. Super Admin sees all
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // 2. Admin Role Access
        // Admins can see all records for modules they are authorized to manage.
        if ($user->hasRole(['admin', 'system_admin'])) {
            $model = $query->getModel();
            $module = $this->getModuleFromResource($model);
            if (!$module || $user->hasPermission("{$module}.edit") || $user->hasPermission("{$module}.delete")) {
                return $query;
            }
        }

        // 3. Ownership-based visibility (Staff/Managers)
        return $query->where('uploaded_by', $user->id);
    }

    /**
     * Helper to map a resource/model to its corresponding permission module.
     */
    private function getModuleFromResource($resource): ?string {
        $class = is_string($resource) ? $resource : get_class($resource);
        
        return match ($class) {
            \App\Models\VideoLecture::class  => \App\Constants\Module::VIDEO_LECTURES,
            \App\Models\CourseOutline::class => \App\Constants\Module::COURSE_OUTLINES,
            \App\Models\LectureNote::class   => \App\Constants\Module::LECTURE_NOTES,
            \App\Models\Assignment::class    => \App\Constants\Module::ASSIGNMENTS,
            \App\Models\Worksheet::class     => \App\Constants\Module::WORKSHEETS,
            \App\Models\ReferenceBook::class => \App\Constants\Module::REFERENCE_BOOKS,
            \App\Models\Research::class      => \App\Constants\Module::REPOSITORY_RESEARCH,
            \App\Models\JournalArticle::class=> \App\Constants\Module::REPOSITORY_JOURNAL,
            \App\Models\Thesis::class        => \App\Constants\Module::REPOSITORY_THESES,
            \App\Models\Proceeding::class    => \App\Constants\Module::REPOSITORY_PROCEEDINGS,
            default                          => null
        };
    }
}
