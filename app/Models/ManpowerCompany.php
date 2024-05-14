<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ManpowerCompany extends Model
{
    protected $table = 'manpower_companies';

    protected $fillable = [
        'name',
        'contract_filename'
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            if (
                $model->contract_filename &&
                Storage::drive('public')->exists('manpower-companies/contract/' . $model->contract_filename)
            ) {
                Storage::drive('public')->delete('manpower-companies/contract/' . $model->contract_filename);
            }
        });
    }
}
