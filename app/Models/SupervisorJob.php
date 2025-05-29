<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisorJob extends Model
{
    use HasFactory;

    protected $table = 'supervisors_jobs';

    protected $fillable = [
        'supervisor_id',
        'job_id',
        'assigned_by_admin_id'
    ];

    /**
     * Get the supervisor (admin with role = supervisor).
     */
    public function supervisor()
    {
        return $this->belongsTo(Admin::class, 'supervisor_id')->where('role', 'supervisor');
    }

    /**
     * Get the job.
     */
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}
