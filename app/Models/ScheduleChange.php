<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleChange extends Model
{
    use HasFactory;

    protected $table = 'schedule_changes';

    protected $fillable = [
        'user_type',
        'comments',
        'user_id',
        'status'
    ];

    /**
     * Get the associated user (Client or User).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user()
    {
        return $this->morphTo();
    }
}
