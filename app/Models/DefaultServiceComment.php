<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultServiceComment extends Model
{
    protected $fillable = [
        'service_id',
        'subservice_id',
        'comments',
    ];

    protected $casts = [
        'comments' => 'array', // Automatically cast JSON to array
    ];

    public function service()
    {
        return $this->belongsTo(Services::class, 'service_id');
    }

    public function subservice()
    {
        return $this->belongsTo(Subservices::class, 'subservice_id');
    }
}
