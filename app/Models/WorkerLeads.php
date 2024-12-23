<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerLeads extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'lng',
        'status',
        'ready_to_get_best_job',
        'ready_to_work_in_house_cleaning',
        'experience_in_house_cleaning',
        'areas_aviv_herzliya_ramat_gan_kiryat_ono_good',
        'none_id_visa',
        'you_have_valid_work_visa',
        'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm',
        'full_or_part_time'
    ];
}