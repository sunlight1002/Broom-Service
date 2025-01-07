<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppBotClientState extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'menu_option',
        'language',
        'auth_id',
        'final'
    ];
}
