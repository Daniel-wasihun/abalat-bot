<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model {
    protected $fillable = [
        'telegram_id',
        'language',
        'first_name'
    ];

    public function favorites() {
        return $this->hasMany(TelegramFavorite::class, 'telegram_id', 'telegram_id');
    }
}
