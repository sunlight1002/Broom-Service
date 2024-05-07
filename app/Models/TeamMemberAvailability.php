<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMemberAvailability extends Model
{
    protected $table = 'team_member_availabilities';

    protected $fillable = [
        'team_member_id',
        'date',
        'start_time',
        'end_time',
    ];
}
