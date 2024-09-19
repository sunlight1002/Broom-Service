<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientMetas extends Model
{
    use HasFactory;

    protected $table = "client_metas";

    protected $fillable = [
        'client_id',
        'key',
        'value',
    ];
}
