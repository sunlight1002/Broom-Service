<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subservices extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_heb',
        'apartment_size',
        'price',
        'service_id'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
