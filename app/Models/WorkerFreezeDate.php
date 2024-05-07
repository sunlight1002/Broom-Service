<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerFreezeDate extends Model
{
    protected $table = 'worker_freeze_dates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
    ];
}
