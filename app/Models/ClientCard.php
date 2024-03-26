<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientCard extends Model
{
    protected $table = 'client_card';

    protected $fillable = [
        'client_id',
        'card_number',
        'card_type',
        'cc_charge',
        'card_holder_id',
        'card_holder_name',
        'valid',
        'card_token',
        'is_default',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'card_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];
}
