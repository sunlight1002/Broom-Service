<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobService extends Model
{
    protected $fillable = [
        'job_id',
        'name',
        'job_hour',
        'freq_name',
        'cycle',
        'period',
        'total',
        'heb_name',
        'service_id',
        'pay_status',
        'order_status',
        'config'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'config' => 'array',
    ];
    public function service()
    {
        return $this->belongsTo(Services::class, 'job_id');
    }
}
