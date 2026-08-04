<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserSession extends Model {

    // ── Status constants ─────────────────────────────────────────────────────
    /** Session is in active use. */
    const STATUS_ACTIVE = 'active';

    /**
     * User intentionally ended the session via the normal logout flow.
     * A subsequent login from this exact device/fingerprint is NOT flagged
     * as suspicious — it is treated as a fresh, ordinary new-device login.
     */
    const STATUS_LOGGED_OUT = 'logged_out';

    /**
     * Session was force-terminated by another device, by a security-email
     * action link, or by the user from the Devices panel.
     * A subsequent login from this exact device/fingerprint IS flagged with
     * the more alarming "terminated session re-login" notification.
     */
    const STATUS_TERMINATED = 'terminated';

    // ── Eloquent config ──────────────────────────────────────────────────────
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_name',
        'device_type',
        'browser',
        'platform',
        'location',
        'last_active_at',
        'is_active',
        'status',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'is_active'      => 'boolean',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActive(Builder $query): Builder {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeTerminated(Builder $query): Builder {
        return $query->where('status', self::STATUS_TERMINATED);
    }

    public function scopeLoggedOut(Builder $query): Builder {
        return $query->where('status', self::STATUS_LOGGED_OUT);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isTerminated(): bool {
        return $this->status === self::STATUS_TERMINATED;
    }

    public function isLoggedOut(): bool {
        return $this->status === self::STATUS_LOGGED_OUT;
    }

    /**
     * Mark this session as logged-out and keep is_active in sync.
     */
    public function markAsLoggedOut(): void {
        $this->update(['status' => self::STATUS_LOGGED_OUT, 'is_active' => false]);
    }

    /**
     * Mark this session as terminated and keep is_active in sync.
     */
    public function markAsTerminated(): void {
        $this->update(['status' => self::STATUS_TERMINATED, 'is_active' => false]);
    }

    // ── Relationships ────────────────────────────────────────────────────────
    public function user() {
        return $this->belongsTo(User::class);
    }
}
