<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramFavorite extends Model {
    protected $fillable = [
        'telegram_id',
        'resource_type',
        'resource_id'
    ];

    public function telegramUser() {
        return $this->belongsTo(TelegramUser::class, 'telegram_id', 'telegram_id');
    }

    public function resource() {
        $modelClass = "App\\Models\\" . $this->resource_type;
        return $modelClass::find($this->resource_id);
    }
}
