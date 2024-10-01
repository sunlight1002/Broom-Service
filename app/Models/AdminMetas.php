<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminMetas extends Model
{
    use HasFactory;

    protected $table = "admin_metas";

    protected $fillable = [
        'key',
        'value',
    ];
}
