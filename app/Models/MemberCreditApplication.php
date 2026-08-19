<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberCreditApplication extends Model
{
    protected $fillable = ['credit_id', 'payment_id', 'amount_applied'];

    protected $casts = ['amount_applied' => 'decimal:2'];

    public function credit(): BelongsTo
    {
        return $this->belongsTo(MemberCredit::class, 'credit_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
