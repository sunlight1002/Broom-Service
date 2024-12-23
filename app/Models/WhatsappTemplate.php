<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'description',
        'message_en',
        'message_heb',
        'message_spa',
        'message_ru',
        'suggestions'
    ];
}
