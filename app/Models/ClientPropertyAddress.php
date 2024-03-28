<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPropertyAddress extends Model
{
    protected $table = "client_property_addresses";

    protected $fillable = [
        'client_id',
        'address_name',
        'city',
        'floor',
        'apt_no',
        'entrence_code',
        'zipcode',
        'geo_address',
        'latitude',
        'longitude',
        'prefer_type',
        'is_dog_avail',
        'is_cat_avail',
        'not_allowed_worker_ids'
    ];
}
