<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookInsights extends Model
{
    use HasFactory;

    protected $table = 'facebook_campaigns';

    protected $fillable = [
        'campaign_name',
        'campaign_id', 
        'date_start', 
        'date_stop',
        'spend', 
        'reach', 
        'clicks',
        'cpc', 
        'cpm', 
        'ctr', 
        'cpp',
        'lead_count',
        'client_count',
        'worker_lead_count',
        'worker_count'
    ];

   // To ensure that lead_count gets incremented
   protected $casts = [
    'lead_count' => 'integer',
    'client_count' => 'integer',
    'worker_lead_count' => 'integer',
    'worker_count' => 'integer'
    ];

}
