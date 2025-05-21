<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Audit extends Model
{
    protected $table;

    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
        'user_type',
        'user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('audit.drivers.database.table', 'audits');
    }

    /**
     * The auditable model (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who performed the action (polymorphic).
     */
    public function user(): MorphTo
    {
        $prefix = config('audit.user.morph_prefix', 'user');
        return $this->morphTo(null, $prefix . '_type', $prefix . '_id');
    }
}
