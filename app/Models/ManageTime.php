<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManageTime extends Model
{
    protected $table = 'manage_time';

    protected $fillable = [
        'start_time',
        'end_time',
        'days',
        'freeze_shift_start_time',
        'freeze_shift_end_time'
    ];
}
