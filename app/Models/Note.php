<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'note',
        'user_id',
        'team_id',
        'role',
        'important'
    ];

    public function team()
    {
        return $this->belongsTo(Admin::class, 'team_id');
    }
}
