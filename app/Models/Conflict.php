<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conflict extends Model
{
    use HasFactory;

    protected $fillable = ['job_id', 'client_id', 'worker_id', 'conflict_client_id', 'conflict_job_id', 'date', 'shift', 'hours'];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function conflictClient()
    {
        return $this->belongsTo(Client::class, 'conflict_client_id');
    }

}
