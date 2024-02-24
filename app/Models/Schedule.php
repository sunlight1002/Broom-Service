<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'team_id', 'booking_status', 'purpose', 'start_time', 'end_time', 'start_date', 'meet_via', 'meet_link', 'is_calendar_event_created', 'meeting_mail_sent_at', 'google_calendar_event_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meeting_mail_sent_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Admin::class, 'team_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
