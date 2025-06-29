<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_id',
        'notification_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the holiday that this notification is for.
     */
    public function holiday()
    {
        return $this->belongsTo(Holiday::class);
    }
} 