<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPropertyAddress extends Model
{
    use HasFactory;

    protected $table = "client_property_addresses";

    protected $fillable = [
        'floor',
        'apt_no',
        'entrence_code',
        'zipcode',
        'geo_address',
        'latitude',
        'longitude',
        'city',
        'client_id',
        'prefer_type',
        'is_dog_avail',
        'is_cat_avail'
    ];
}
