<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSchedule extends Model
{
    protected $table = 'service_schedules';

    protected $fillable = [
        'name',
        'name_heb',
        'cycle',
        'period',
        'status',
        'color_code'
    ];

    public function tasks()
    {
        return $this->hasMany(TaskManagement::class, 'frequency_id');
    }
}
