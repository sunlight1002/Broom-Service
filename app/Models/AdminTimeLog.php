<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminTimeLog extends Model
{
    use HasFactory;

    protected $table = 'admin_time_logs';

    protected $fillable = [
        'admins_id',
        'start_timer',
        'end_timer',
        'start_location',
        'end_location',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'difference_minutes',
    ];

    protected $casts = [
        'start_timer' => 'datetime',
        'end_timer' => 'datetime',
    ];

    /**
     * Relationship to the Admin model
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admins_id');
    }
}
