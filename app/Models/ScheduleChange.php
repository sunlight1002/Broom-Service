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
        'reason',
        'comments',
        'user_id',
        'status',
        'team_response',
        'scheduled_date'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($scheduleChange) {
            if (!$scheduleChange->scheduled_date) {
                $scheduleChange->scheduled_date = now()->toDateString();
            }
        });
    }

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
