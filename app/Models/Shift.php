<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shift_change';

    protected $fillable = [
        'contract_id',
        'repetency',
        'old_freq',
        'new_freq',
        'shift_date',
        'shift_time',
        'from',
        'to'
    ];
}
