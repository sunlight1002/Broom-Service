<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobWorkerShift extends Model
{
    protected $table = 'job_worker_shifts';

    protected $fillable = ['job_id', 'starting_at', 'ending_at'];
}
