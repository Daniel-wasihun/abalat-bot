<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserInfo extends Model {
    use HasFactory, SoftDeletes;

    protected $table = 'user_info';

    protected $fillable = [
        'user_id',
        'registration_id',
        'gender',
        'phone_number',
        'date_of_birth',
        'address',
        'profile_picture',
        'status',
        'suspension_reason',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
