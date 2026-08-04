<?php

namespace App\Traits;

use App\Helpers\Response;
use App\Services\BackMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Trait InteractsWithCsv
 * 
 * Provides standardized CSV import (sync/async) and export capabilities
 * for controllers and other services.
 */
trait InteractsWithCsv {
    /**
     * Download a CSV template or export data
     */
    protected function downloadCsvTemplate(string $filename, array $headers, array $sampleRow = []) {
        $callback = function () use ($headers, $sampleRow) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            if (!empty($sampleRow)) {
                fputcsv($file, $sampleRow);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}.csv",
        ]);
    }

    /**
     * Synchronous CSV Import
     */
    protected function importFromCsv(Request $request, array $config) {
        $rules = array_merge([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ], $config['validation_rules'] ?? []);

        $request->validate($rules);

        $file = $request->file('file');
        $filePath = $file->store('imports');
        $handle = fopen(Storage::path($filePath), 'r');
        $rawHeader = fgetcsv($handle);

        if (!$rawHeader) {
            fclose($handle);
            return Response::_422(BackMessage::get('csv_empty_or_invalid'));
        }

        // Remove BOM if exists
        $rawHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', $rawHeader[0]);

        $header = array_map('trim', $rawHeader);
        $headerMap = array_flip($header);
        $requiredColumns = $config['required_columns'] ?? [];

        foreach ($requiredColumns as $col) {
            if (!isset($headerMap[$col])) {
                fclose($handle);
                return Response::_422(BackMessage::get('missing_required_column', ['column' => $col]) . ". " . BackMessage::get('import_check_template'));
            }
        }

        $importedCount = 0;
        $failedCount = 0;
        $errors = [];
        $successLog = [];
        $rowNum = 2;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;

            $rowData = [];
            foreach ($headerMap as $col => $index) {
                $val = isset($row[$index]) ? trim($row[$index]) : null;
                $rowData[$col] = ($val === '') ? null : $val;
            }

            DB::beginTransaction();
            try {
                $msg = $config['callback']($rowData, $rowNum);
                DB::commit();
                $importedCount++;

                if (is_string($msg)) $successLog[] = $msg;
            } catch (ValidationException $ve) {
                DB::rollBack();
                $failedCount++;
                $errors[] = $this->formatRowError($rowNum, $ve, $rowData, $config['attributes'] ?? []);
            } catch (\Throwable $e) {
                DB::rollBack();
                $failedCount++;
                $errors[] = BackMessage::get('validation.row_error', ['row' => $rowNum, 'error' => $e->getMessage()]);
            }

            $rowNum++;
        }

        fclose($handle);
        if (Storage::exists($filePath)) Storage::delete($filePath);

        return Response::_200([
            'message' => BackMessage::get($config['success_key'] ?? 'import_success', ['count' => $importedCount]),
            'imported_count' => $importedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
            'success_log' => $successLog,
        ]);
    }

    /**
     * Asynchronous CSV Import via Job
     */
    protected function importFromCsvAsync(Request $request, array $config) {
        $rules = array_merge([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ], $config['validation_rules'] ?? []);

        $request->validate($rules);

        $file = $request->file('file');
        $filePath = $file->store('imports');

        // Count lines for progress tracking
        $lineCount = 0;
        $path = Storage::path($filePath);
        $handle = fopen($path, 'r');
        while (!feof($handle)) {
            if (trim(fgets($handle)) !== '') $lineCount++;
        }
        fclose($handle);
        $lineCount = max(0, $lineCount - 1); // Subtract header

        $importResult = \App\Models\ImportResult::create([
            'user_id' => $request->user()->id,
            'type' => $config['type'] ?? 'generic_import',
            'status' => 'pending',
            'total_rows' => $lineCount,
            'file_name' => $file->getClientOriginalName(),
        ]);

        \App\Jobs\PerformImport::dispatch(
            $importResult->id,
            $filePath,
            [
                'handler_class' => get_class($this),
                'handler_method' => $config['handler_method'] ?? 'processImportRow',
                'attributes' => $config['attributes'] ?? [],
                'locale' => app()->getLocale(),
            ],
            $config['context'] ?? []
        );

        return Response::_200([
            'message' => BackMessage::get('import_started'),
            'import_id' => $importResult->id,
            'status' => 'pending',
            'import' => $importResult
        ]);
    }

    /**
     * Format validation errors for a specific row
     */
    private function formatRowError(int $rowNum, ValidationException $ve, array $rowData, array $attributes): string {
        $rowErrors = [];
        foreach ($ve->errors() as $field => $messages) {
            $label = $attributes[$field] ?? $field;
            $value = $rowData[$field] ?? null;
            $valueDisplay = $value ? " <span class='text-brand-yellow font-bold'>({$value})</span>" : "";

            foreach ($messages as $msg) {
                $cleanMsg = preg_replace('/^The\s+/i', '', trim($msg));
                if ($value && str_contains($cleanMsg, "($value)")) {
                    $cleanMsg = str_replace("($value)", "", $cleanMsg);
                }
                $rowErrors[] = "<span class='text-accent font-bold'>[{$label}]</span>: " . trim($cleanMsg) . $valueDisplay;
            }
        }

        return BackMessage::get('validation.row_error', [
            'row' => $rowNum,
            'error' => implode('<br>', $rowErrors)
        ]);
    }
}
