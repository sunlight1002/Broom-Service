<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskManagement extends Model
{
    use HasFactory ,SoftDeletes;

    protected $table = 'task_management';

    protected $fillable = [
        'phase_id', 
        'task_name', 
        'status', 
        'priority', 
        'description',
        'due_date',
        'user_id',
        'user_type',
        'sort_order',
        'frequency_id',
        'repeatancy',
        'until_date',
        'next_start_date',
        'cycle_counter'
    ];

    public function users()
    {
        return $this->morphedByMany(Admin::class, 'assignable' ,'task_workers','task_management_id', 'assignable_id')
        ->wherePivot('assignable_type', Admin::class);
    }

    public function workers()
    {
        return $this->morphedByMany(User::class, 'assignable','task_workers', 'task_management_id', 'assignable_id')
        ->wherePivot('assignable_type', User::class);
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function taskWorker()
    {
        return $this->hasMany(TaskWorker::class);
    }

    public function serviceSchedule()
    {
        return $this->belongsTo(ServiceSchedule::class, 'frequency_id');
    }

}