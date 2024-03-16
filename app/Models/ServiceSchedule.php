<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSchedule extends Model
{
    use HasFactory;

    protected $table = 'service_schedules';

    protected $fillable = [
        'name',
        'name_heb',
        'cycle',
        'period',
        'status',
        'color_code'
    ];
}
