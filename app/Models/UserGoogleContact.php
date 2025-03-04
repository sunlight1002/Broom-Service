<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGoogleContact extends Model
{
    use HasFactory;

    protected $table = 'user_google_contacts';

    protected $fillable = [
        'user_id',
        'admin_id',
        'contact_id',
    ];

    /**
     * Relationship with User model.
     * A Google contact belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with Admin model.
     * A Google contact belongs to an admin (HR), but it's nullable.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
