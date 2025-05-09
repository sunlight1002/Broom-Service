<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'created_date', 'status_changed_date', 'changes_status', 'reason', 'reschedule_date', 'reschedule_time', 'voice_bot_call_date', 'voice_bot_call_time', 'changed_by'];
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
