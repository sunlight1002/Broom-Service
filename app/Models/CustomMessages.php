<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomMessages extends Model
{
    use HasFactory;
    protected $table = "custom_message_send";

    protected $fillable = [
        'type', 
        'status',
        'message_en',
        'message_heb'
    ];
}
