<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginThrottle extends Model {
    protected $fillable = ['email', 'ip_address', 'user_agent', 'attempts', 'locked_until', 'lockout_count'];

    protected $casts = [
        'locked_until' => 'datetime',
    ];
}
