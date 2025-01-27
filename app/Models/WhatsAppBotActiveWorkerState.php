<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppBotActiveWorkerState extends Model
{
    use HasFactory;
    protected $table = 'whats_app_bot_activeworker_states';

    protected $fillable = [
        'worker_id',
        'menu_option',
        'lng',
        'comment',
        'final',
    ];


    public function worker()
    {
        return $this->belongsTo(User::class,'worker_id');
    }
}
