<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;
    protected $fillable = [
        'start_date',
        'end_date',
        'full_day',
        'half_day',
        'first_half',
        'second_half',
        'holiday_name'
    ];
}
