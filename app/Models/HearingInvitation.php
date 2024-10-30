<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HearingInvitation extends Model
{
    use HasFactory;

    protected $table = 'hearing_invitations';

    protected $fillable = [
        'user_id',
        'team_id',
        'start_date',
        'start_time',
        'end_time',
        'meet_via',
        'meet_link',
        'purpose',
        'booking_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'team_id', 'id');
    }

    // public function address()
    // {
    //     return $this->belongsTo(Address::class);
    // }
}
