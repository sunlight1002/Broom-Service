<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SickLeave extends Model
{
    use HasFactory,SoftDeletes;


    protected $fillable = ['worker_id', 'start_date', 'end_date', 'doctor_report_path', 'status','rejection_comment','reason_for_leave'];

    public function user()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}