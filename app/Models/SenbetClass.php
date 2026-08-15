<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SenbetClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'intake_capacity_per_section',
        'number_of_sections',
        'is_active',
    ];
}
