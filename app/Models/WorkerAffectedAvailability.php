<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WorkerAffectedAvailability extends Model
{
    protected $table = 'worker_affected_availabilities';

    protected $fillable = [
        'worker_id',
        'old_values',
        'new_values',
        'status',
        'responder_id',
        'responder_type',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function responder()
    {
        return $this->morphTo();
    }
}
