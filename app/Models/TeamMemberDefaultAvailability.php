<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMemberDefaultAvailability extends Model
{
    protected $table = 'team_member_default_availabilities';

    protected $fillable = [
        'team_member_id',
        'start_time',
        'end_time',
        'until_date',
    ];

    protected $casts = [
        'until_date' => 'datetime:Y-m-d',
    ];
}
