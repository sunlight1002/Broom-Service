<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refunds extends Model
{
    protected $table = 'refunds';

    protected $fillable = [
        'invoice_id',
        'invoice_icount_id',
        'refrence',
        'message',
    ];
}
