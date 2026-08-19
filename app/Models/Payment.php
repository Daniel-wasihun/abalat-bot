<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Payment extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'user_id',
        'for_year',
        'for_month',
        'work_status',
        'base_amount',
        'fine_amount',
        'total_amount_due',
        'amount_paid',
        'due_date',
        'status',
        'paid_at',
        'recorded_by',
    ];

    protected $appends = ['balance'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'due_date' => 'date',
            'base_amount' => 'decimal:2',
            'fine_amount' => 'decimal:2',
            'total_amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function getBalanceAttribute()
    {
        return max(0, $this->total_amount_due - $this->amount_paid);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function creditApplications(): HasMany
    {
        return $this->hasMany(MemberCreditApplication::class);
    }
}
