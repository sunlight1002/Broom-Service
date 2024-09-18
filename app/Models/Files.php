<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Files extends Model
{
    protected $fillable = [
        'user_id',
        'meeting',
        'role',
        'note',
        'type',
        'file',
    ];
}
