<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HearingProtocol extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdf_name',
        'file',
        'worker_id',
        'team_id',
        'hearing_invitation_id',
        'comment',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function team()
    {
        return $this->belongsTo(Admin::class, 'team_id');
    }

    public function hearingInvitation()
    {
        return $this->belongsTo(HearingInvitation::class, 'hearing_invitation_id');
    }
}
