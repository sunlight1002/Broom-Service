<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkippedComment extends Model
{
    protected $fillable = ['comment_id', 'request_text','response_text', 'status'];

    // Define relationship with JobComments
    public function comment()
    {
        return $this->belongsTo(JobComments::class);
    }
}