<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPropertyAddress extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = "client_property_addresses";

    protected $fillable = [
        'client_id',
        'address_name',
        'city',
        'floor',
        'apt_no',
        'entrence_code',
        'zipcode',
        'geo_address',
        'latitude',
        'longitude',
        'parking',
        'key',
        'lobby',
        'prefer_type',
        'is_dog_avail',
        'is_cat_avail',
        'not_allowed_worker_ids'
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            $comments = $model->comments()->get();
            foreach ($comments as $key => $comment) {
                $comment->delete();
            }
        });
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'relation');
    }
}
