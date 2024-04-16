<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JobComments extends Model
{
    protected $table = 'job_comments';

    protected $fillable = ['job_id', 'comment', 'name', 'comment_for', 'commenter_type', 'commenter_id'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->commenter_id = Auth::id();
                $model->commenter_type = get_class(Auth::user());
            }
        });
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function commenter()
    {
        return $this->morphTo();
    }
}
