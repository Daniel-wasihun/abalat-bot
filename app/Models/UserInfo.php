<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserInfo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_info';

    protected $fillable = [
        'user_id',
        'registration_id',
        'father_name',
        'grandfather_name',
        'christian_name',
        'spiritual_father_name',
        'gender',
        'phone_number',
        'address',
        'sub_city',
        'woreda',
        'house_number',
        'profile_picture',
        'status',
        'suspension_reason',
    ];

    /**
     * Generate the next unique registration ID (e.g. DBSS000001 → DBSS000002).
     */
    public static function generateNextRegistrationId(): string
    {
        $last = self::where('registration_id', 'like', 'DBSS%')
            ->orderByDesc('registration_id')
            ->value('registration_id');

        $next = $last ? (int) preg_replace('/\D/', '', $last) + 1 : 1;

        return 'DBSS' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
