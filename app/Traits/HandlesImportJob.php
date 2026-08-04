<?php

namespace App\Traits;

use App\Events\ImportProgressBroadcast;
use App\Models\ImportResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Trait for handling common CSV import job functionality
 * Eliminates boilerplate code across all import jobs
 */
trait HandlesImportJob {
    /**
     * Maximum number of errors/successes to store in database
     */
    protected int $logLimit = 1000;

    /**
     * Update import progress in database and broadcast to frontend
     *
     * @param ImportResult $importResult
     * @param int $processed Number of rows processed
     * @param int $imported Number of rows successfully imported
     * @param array $errors Array of error messages
     * @param array $successes Array of success messages
     * @param string $status Current status (processing, completed, failed)
     */
    protected function updateProgress(
        ImportResult $importResult,
        int $processed,
        int $imported,
        array $errors,
        array $successes = [],
        string $status = 'processing'
    ): void {
        $isFinal = in_array($status, ['completed', 'failed']);

        $updateData = [
            'processed_rows' => $processed,
            'imported_count' => $imported,
            'status' => $status
        ];

        // Only update heavy JSON logs periodically or on final status to severely reduce DB writes/locks
        if ($isFinal || $processed % 50 === 0) {
            $updateData['errors'] = $this->limitArray($errors, $this->logLimit);
            $updateData['success_log'] = $this->limitArray($successes, $this->logLimit);
        }

        $importResult->update($updateData);

        // Broadcast minimal payload for real-time updates
        $this->broadcastProgress($importResult, $processed, $imported, count($errors), $status);
    }

    /**
     * Broadcast import progress to frontend via websockets
     */
    protected function broadcastProgress(
        ImportResult $importResult,
        int $processed,
        int $imported,
        int $errorCount,
        string $status
    ): void {
        try {
            broadcast(new ImportProgressBroadcast($importResult->id, [
                'status' => $status,
                'processed' => $processed,
                'total' => $importResult->total_rows,
                'imported' => $imported,
                'error_count' => $errorCount,
            ]));
        } catch (\Throwable $e) {
            // Silent fail - don't interrupt import process
            Log::debug("Broadcast failed for import {$importResult->id}: {$e->getMessage()}");
        }
    }

    /**
     * Limit array size to prevent database payload issues
     */
    protected function limitArray(array $data, int $limit): array {
        return count($data) > $limit ? array_slice($data, 0, $limit) : $data;
    }

    /**
     * Parse CSV header and remove BOM if present
     *
     * @param resource $handle File handle
     * @return array Header columns
     * @throws \Exception
     */
    protected function parseCsvHeader($handle): array {
        $header = fgetcsv($handle);

        if (!$header) {
            throw new \Exception("Empty CSV file.");
        }

        // Remove UTF-8 BOM from first column if present
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        return $header;
    }

    /**
     * Create header mapping for column access
     *
     * @param array $header
     * @param bool $lowercase Whether to lowercase keys
     * @return array
     */
    protected function createHeaderMap(array $header, bool $lowercase = false): array {
        $cleanHeader = array_map('trim', $header);

        if ($lowercase) {
            $cleanHeader = array_map('strtolower', $cleanHeader);
        }

        return array_flip($cleanHeader);
    }

    /**
     * Clean and trim CSV row values
     */
    protected function cleanRow(array $row): array {
        return array_map(function ($value) {
            $trimmed = trim((string)$value);
            return $trimmed === '' ? null : $trimmed;
        }, $row);
    }

    /**
     * Handle job failure - update status and clean up
     */
    public function failed(\Throwable $exception): void {
        $importResult = ImportResult::find($this->importResultId);

        if ($importResult) {
            $errors = is_array($importResult->errors) ? $importResult->errors : [];
            $errors[] = "JOB FAILED: " . $exception->getMessage();

            $importResult->update([
                'status' => 'failed',
                'errors' => $errors
            ]);
        }

        $this->cleanupFile();
    }

    /**
     * Clean up temporary import file
     */
    protected function cleanupFile(): void {
        if (isset($this->filePath) && Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }

    /**
     * Send completion email to user
     */
    protected function sendCompletionEmail(
        $user,
        ImportResult $importResult,
        int $totalProcessed,
        int $importedCount,
        int $failedCount,
        array $errors
    ): void {
        if (!$user) {
            return;
        }

        try {
            Mail::to($user->email)->send(new \App\Mail\ImportStatusMail(
                $importResult,
                $totalProcessed,
                $importedCount,
                $failedCount,
                $errors
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send import status email to {$user->email}: {$e->getMessage()}");
        }
    }

    /**
     * Format validation errors for display
     */
    protected function formatValidationErrors(array $errors, array $labels): array {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            $label = $labels[$field] ?? $field;

            foreach ($messages as $msg) {
                $cleanMsg = preg_replace('/^The\s+/i', '', trim($msg));
                $formatted[] = "<span class='text-accent font-normal'>[{$label}]</span>: {$cleanMsg}";
            }
        }

        return $formatted;
    }

    /**
     * Get value from CSV row by column name
     */
    protected function getColumnValue(array $row, array $headerMap, string $column): ?string {
        return isset($headerMap[$column]) && isset($row[$headerMap[$column]])
            ? $row[$headerMap[$column]]
            : null;
    }
}
