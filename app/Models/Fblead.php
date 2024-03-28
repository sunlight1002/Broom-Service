<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fblead extends Model
{
    protected $table = 'fb_leads';

    protected $fillable = [
        'challenge'
    ];
}
