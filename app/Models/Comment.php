<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Comment extends Model
{
    protected $table = 'comments';

    protected $fillable = [
        'relation_type',
        'relation_id',
        'comment',
        'user_id',
        'commenter_type',
        'commenter_id',
        'valid_till'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->commenter_id = Auth::id();
                $model->commenter_type = get_class(Auth::user());
            }
        });

        static::deleting(function ($model) {
            $attachments = $model->attachments()->get();
            foreach ($attachments as $key => $attachment) {
                $attachment->delete();
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
