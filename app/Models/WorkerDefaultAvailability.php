<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerDefaultAvailability extends Model
{
    protected $table = 'worker_default_availabilities';

    protected $fillable = [
        'user_id',
        'weekday',
        'start_time',
        'end_time',
        'until_date',
    ];

    protected $casts = [
        'until_date' => 'datetime:Y-m-d',
    ];
}
