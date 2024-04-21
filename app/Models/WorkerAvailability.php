<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerAvailability extends Model
{
    use HasFactory;

    protected $table = 'worker_avialibilties';

    protected $fillable = [
        'user_id',
        'day',
        'date',
        'start_time',
        'end_time',
        'working',
        'status',
    ];

    protected $casts = [
        'working' => 'array',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
