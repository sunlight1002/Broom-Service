<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discount'; // Optional if your table name matches Laravel convention

    protected $fillable = [
        'client_ids',
        'service_ids',
        'days',
        'type',
        'value',
        'applied_client_ids'
        // 'amount',
        // 'percentage',
    ];

    /**
     * Check if discount is percentage based
     */
    public function isPercentage()
    {
        return $this->type === 'percentage';
    }

    /**
     * Check if discount is fixed amount
     */
    public function isFixedAmount()
    {
        return $this->type === 'fixed';
    }
}
