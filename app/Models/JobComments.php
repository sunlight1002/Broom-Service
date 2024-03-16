<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobComments extends Model
{
    protected $table = 'job_comments';

    protected $fillable = ['job_id', 'comment', 'name', 'role'];
}
