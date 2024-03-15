<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobHours extends Model
{
    protected $table = 'job_hours';

    protected $fillable = ['job_id', 'worker_id', 'start_time', 'end_time', 'time_diff'];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
