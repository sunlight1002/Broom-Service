<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentJobs extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'client_id',
        'worker_id',
        'offer_id',
        'contract_id',
        'schedule_id',
        'schedule',
        'parent_job_id',
        'start_date',
        'status',
        'next_start_date',
        'keep_prev_worker',
    ];
}
