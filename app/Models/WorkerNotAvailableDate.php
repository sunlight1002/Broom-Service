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
}
