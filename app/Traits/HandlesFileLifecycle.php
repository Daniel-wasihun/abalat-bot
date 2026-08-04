<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Trait to standardize file lifecycle management across the application.
 * Ensures consistent sanitization and robust storage cleanup.
 */
trait HandlesFileLifecycle {
    /**
     * Standardized professional Unicode-aware filename sanitization.
     * Replaces spaces with hyphens and removes dangerous characters while
     * preserving Unicode letters and numbers (Amharic, Arabic, etc.).
     */
    protected function sanitizeFilename($file): string {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        
        // Use Unicode property escapes to keep letters (\p{L}) and numbers (\p{N})
        // from any language, plus dots, underscores, and hyphens.
        $sanitized = preg_replace('/[^\p{L}\p{N}\._\-]/u', '', str_replace(' ', '-', $originalName));
        
        // Ensure we don't return an empty string if sanitization removed everything
        if (empty($sanitized)) {
            $sanitized = 'file_' . uniqid();
        }

        return time() . '_' . $sanitized . '.' . $extension;
    }

    /**
     * Robust file deletion from the public storage disk.
     * Includes existence check to prevent errors.
     */
    protected function deleteFile(?string $path): void {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Validates if a file with the same name and size already exists in the given model.
     * Throws a validation exception if a duplicate is found.
     */
    protected function validateFileUniqueness($file, string $modelClass, ?int $ignoreId = null, string $pathColumn = 'file_path', string $sizeColumn = 'file_size'): void {
        if (!$file) return;

        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();

        // Sanitize the name part to match how it's stored (excluding the timestamp)
        $namePart = preg_replace('/[^\p{L}\p{N}\._\-]/u', '', str_replace(' ', '-', pathinfo($originalName, PATHINFO_FILENAME)));
        $extension = $file->getClientOriginalExtension();
        $suffix = '_' . $namePart . '.' . $extension;

        $query = $modelClass::where($sizeColumn, $size)
            ->where($pathColumn, 'LIKE', '%' . $suffix);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            abort(422, \App\Services\BackMessage::get('file_already_exists', ['filename' => $originalName]));
        }
    }
}
