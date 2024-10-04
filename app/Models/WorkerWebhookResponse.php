<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerWebhookResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'entry_id',
        'message',
        'number',
        'data',
        'flex',
        'read',
    ];

}
