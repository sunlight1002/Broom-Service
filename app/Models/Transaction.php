<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Transaction extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'transactions';

    protected $fillable = [
        'session_id',
        'transaction_id',
        'client_id',
        'amount',
        'currency',
        'transaction_at',
        'status',
        'type',
        'description',
        'source',
        'destination',
        'metadata',
        'gateway',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'double',
        'transaction_at' => 'datetime',
        'metadata' => 'array',
    ];
}
