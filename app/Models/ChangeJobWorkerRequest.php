<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeJobWorkerRequest extends Model
{
    use HasFactory;

    protected $table = 'change_job_worker_requests';

    protected $fillable = [
        'job_id',
        'client_id',
        'worker_id',
        'date',
        'shifts',
        'repeatancy',
        'repeat_until_date',
        'status',
        'action_by_type',
        'action_by_id',
    ];
}

