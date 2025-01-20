<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppBotActiveClientState extends Model
{
    use HasFactory;

    protected $table = 'whats_app_bot_activeclient_states';

    protected $fillable = [
        'client_id',
        'menu_option',
        'lng',
        'comment',
        'from',
        'client_phone',
        'final',
    ];
}
