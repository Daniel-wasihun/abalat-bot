<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasSorting {
    /**
     * Apply sorting to the Eloquent query based on the incoming request.
     *
     * The method resolves sorting in the following priority:
     *   1. If `sort_by` is present and in the whitelist:
     *      a. Delegates to a model method `sortBy{Field}($query, $order)` if it exists.
     *      b. Handles JSON arrow expressions (e.g. `title->en`) via orderByRaw.
     *      c. Falls back to a plain `orderBy($col, $dir)`.
     *   2. If no valid `sort_by` param or param is not whitelisted:
     *      a. Invokes the provided $fallback Closure (receives the Builder).
     *      b. If no fallback is given, uses the global `chronological()` macro.
     *
     * @param  Builder      $query
     * @param  Request      $request
     * @param  array        $allowedSorts  Whitelisted sortable column/relation names
     * @param  Closure|null $fallback      Default ordering: fn(Builder $q) => $q->orderBy(...)
     * @return Builder
     */
    public function scopeApplySort(
        Builder $query,
        Request $request,
        array $allowedSorts = [],
        ?Closure $fallback = null
    ): Builder {
        $sortBy    = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            // Convert e.g. "academic_year" → "sortByAcademicYear"
            $methodName = 'sortBy' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $sortBy)));

            if (method_exists($this, $methodName)) {
                return $this->$methodName($query, $sortOrder);
            }

            // JSON arrow path e.g. "title->en"
            if (str_contains($sortBy, '->')) {
                $query->orderByRaw("{$sortBy} {$sortOrder}");
                return $query;
            }

            $query->orderBy($sortBy, $sortOrder);
            return $query;
        }

        // Apply custom fallback or the global chronological macro
        if ($fallback instanceof Closure) {
            $fallback($query);
            return $query;
        }

        return $query->chronological();
    }
}
