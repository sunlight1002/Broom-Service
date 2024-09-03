<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSchedule extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

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
