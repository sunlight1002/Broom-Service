<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    protected $table = "leadstatus";

    protected $fillable = [
        'client_id',
        'lead_status'
    ];
}
