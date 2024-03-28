<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    protected $fillable = [
        'name',
        'heb_name',
        'template',
        'status',
        'color_code'
    ];
}
