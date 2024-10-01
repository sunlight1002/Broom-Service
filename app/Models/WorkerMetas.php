<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerMetas extends Model
{
    use HasFactory;

    protected $table = "worker_metas";

    protected $fillable = [
        'worker_id',
        'key',
        'value',
    ];
}
