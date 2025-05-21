<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    protected $fillable = [
        'name',
        'heb_name',
        'template',
        'status',
        'color_code',
        'icon'
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            $comments = $model->comments()->get();
            foreach ($comments as $key => $comment) {
                $comment->delete();
            }
        });
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'relation');
    }
}
