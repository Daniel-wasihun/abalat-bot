<?php

namespace App\Traits;

use App\Models\Library;
use App\Scopes\LibraryScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait BelongsToLibrary
 * 
 * Handles library-based data isolation (scoping) and automatic assignment
 * of records to the user's current library branch.
 */
trait BelongsToLibrary {
    /**
     * Boot the trait to apply global scope and creation listener.
     */
    public static function bootBelongsToLibrary() {
        if (static::shouldApplyLibraryScope()) {
            static::addGlobalScope('library', new LibraryScope());
        }

        static::creating(function ($model) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user && !$model->library_id && !$user->canManageAllLibraries()) {
                $model->library_id = $user->library_id;
            }
        });
    }

    /**
     * Determine if the library isolation scope should be applied.
     * Models can override this to return false if they want to disable the scope
     * while still benefiting from automatic library_id assignment on creation.
     */
    protected static function shouldApplyLibraryScope(): bool {
        return true;
    }

    /**
     * Each model using this trait belongs to a library branch.
     */
    public function library(): BelongsTo {
        return $this->belongsTo(Library::class, 'library_id');
    }
}
