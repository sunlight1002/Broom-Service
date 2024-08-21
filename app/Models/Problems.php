<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problems extends Model
{
    use HasFactory;
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    protected $fillable = [
        'client_id',
        'job_id',
        'problem'
    ];
}
