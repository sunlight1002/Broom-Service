<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerInvitation extends Model
{
    protected $table = 'worker_invitations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        "worker_id",
        'birth_date',
        'company',
        'manpower_company_name',
        'form_101',
        'contact',
        'safety',
        'insurance',
        'country',
        'visa_id',
        'is_invitation_sent',
        'lng',
    ];
}
