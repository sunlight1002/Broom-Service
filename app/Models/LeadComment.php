<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadComment extends Model
{
    protected $fillable = [
        'lead_id',
        'team_id',
        'comment',
    ];

    public function team()
    {
        return $this->belongsTo(Admin::class, 'team_id');
    }
}
