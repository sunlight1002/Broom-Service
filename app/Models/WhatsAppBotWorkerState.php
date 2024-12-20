<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppBotWorkerState extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_lead_id',
        'step',
        'language',
        'first_reminder',
        'second_reminder',
        'final_reminder',
    ];
}
