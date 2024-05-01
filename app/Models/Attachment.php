<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $table = 'attachments';

    protected $fillable = ['file_name', 'original_name'];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            if (Storage::drive('public')->exists('uploads/attachments/' . $model->file_name)) {
                Storage::drive('public')->delete('uploads/attachments/' . $model->file_name);
            }
        });
    }

    public function attachable()
    {
        return $this->morphTo();
    }
}
