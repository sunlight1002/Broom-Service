<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = ['tokenable_id', 'tokenable_type', 'token', 'expires_at'];

    public function tokenable()
    {
        return $this->morphTo();
    }
}
