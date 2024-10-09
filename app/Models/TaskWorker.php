<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskWorker extends Model
{
    use HasFactory ,SoftDeletes;
    protected $fillable = ['task_management_id', 'assignable_id', 'assignable_type'];

    public function task()
    {
        return $this->belongsTo(TaskManagement::class,'task_management_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }

}