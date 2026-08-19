<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberCredit extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'remaining', 'source_type',
        'source_payment_id', 'source_transaction_id', 'created_by', 'note',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'remaining' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourcePayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'source_payment_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(MemberCreditApplication::class, 'credit_id');
    }
}
