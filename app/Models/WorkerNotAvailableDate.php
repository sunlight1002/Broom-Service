<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerNotAvailableDate extends Model
{
    protected $table = 'worker_not_availble_dates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime:Y-m-d',
    ];
}
