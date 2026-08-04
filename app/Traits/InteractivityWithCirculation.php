<?php

namespace App\Traits;

use App\Models\Borrow;
use App\Models\SpotReading;
use App\Models\User;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait InteractsWithCirculation Context
 * 
 * Provides unified accessors and sorting logic for models that contextually
 * belong to either a long-term Borrow transaction or a short-term SpotReading session.
 */
trait InteractivityWithCirculation {

    /**
     * Resolve the unique physical item identifier (Barcode).
     */
    public function getBookBarcodeAttribute(): ?string {
        if ($this->borrow_id && $this->borrow) {
            return $this->borrow->checkout_barcode ?? $this->borrow->bookCopy?->barcode?->barcode;
        }
        if ($this->spot_reading_id && $this->spotReading) {
            return $this->spotReading->bookCopy?->barcode?->barcode;
        }
        return null;
    }

    /**
     * Resolve the bibliographic title.
     */
    public function getBookTitleAttribute() {
        if ($this->borrow_id && $this->borrow) {
            return $this->borrow->book?->title;
        }
        if ($this->spot_reading_id && $this->spotReading) {
            return $this->spotReading->book?->title;
        }
        return null;
    }

    /**
     * Resolve the stakeholder's identity.
     */
    public function getBorrowerNameAttribute() {
        if ($this->borrow_id && $this->borrow) {
            return $this->borrow->borrower?->name;
        }
        if ($this->spot_reading_id && $this->spotReading) {
            return $this->spotReading->user?->name;
        }
        return null;
    }

    /**
     * Unified Sorting by Borrower Name across both transaction types.
     */
    public function sortByBorrowerName(Builder $query, string $sortOrder): Builder {
        $locale = app()->getLocale();
        $table  = $this->getTable();
        return $query->leftJoin('borrows', "{$table}.borrow_id", '=', 'borrows.id')
            ->leftJoin('users as b_u', 'borrows.borrower_id', '=', 'b_u.id')
            ->leftJoin('spot_readings', "{$table}.spot_reading_id", '=', 'spot_readings.id')
            ->leftJoin('users as s_u', 'spot_readings.user_id', '=', 's_u.id')
            ->orderByRaw("COALESCE(b_u.name->>'{$locale}', b_u.name->>'en', s_u.name->>'{$locale}', s_u.name->>'en') {$sortOrder}")
            ->select("{$table}.*");
    }

    /**
     * Unified Sorting by Book Title across both transaction types.
     */
    public function sortByBookTitle(Builder $query, string $sortOrder): Builder {
        $locale = app()->getLocale();
        $table  = $this->getTable();
        return $query->leftJoin('borrows as b_t', "{$table}.borrow_id", '=', 'b_t.id')
            ->leftJoin('books as b_b', 'b_t.book_id', '=', 'b_b.id')
            ->leftJoin('spot_readings as s_t', "{$table}.spot_reading_id", '=', 's_t.id')
            ->leftJoin('books as s_b', 's_t.book_id', '=', 's_b.id')
            ->orderByRaw("COALESCE(b_b.title->>'{$locale}', b_b.title->>'en', s_b.title->>'{$locale}', s_b.title->>'en') {$sortOrder}")
            ->select("{$table}.*");
    }

    /**
     * Unified Sorting by Barcode across both transaction types.
     */
    public function sortByBookBarcode(Builder $query, string $sortOrder): Builder {
        $table = $this->getTable();
        return $query->leftJoin('borrows as bc_b', "{$table}.borrow_id", '=', 'bc_b.id')
            ->leftJoin('barcodes as b_bc', 'bc_b.book_copy_id', '=', 'b_bc.book_copy_id')
            ->leftJoin('spot_readings as bc_s', "{$table}.spot_reading_id", '=', 'bc_s.id')
            ->leftJoin('barcodes as s_bc', 'bc_s.book_copy_id', '=', 's_bc.book_copy_id')
            ->orderByRaw("COALESCE(bc_b.checkout_barcode, b_bc.barcode, s_bc.barcode) {$sortOrder}")
            ->select("{$table}.*");
    }
}
