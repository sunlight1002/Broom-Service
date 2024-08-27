<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory ,SoftDeletes;
    protected $table = 'task_comments';

    protected $fillable = ['task_management_id', 'commentable_id', 'commentable_type', 'comment'];

    public function task()
    {
        return $this->belongsTo(TaskManagement::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

}
