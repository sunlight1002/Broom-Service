<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $table = 'claims';

    protected $fillable = [
        'worker_id',
        'admin_id',
        'claim',
        'hearing_invitation_id',
        'file',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function hearingInvitation()
    {
        return $this->belongsTo(HearingInvitation::class, 'hearing_invitation_id');
    }
}
