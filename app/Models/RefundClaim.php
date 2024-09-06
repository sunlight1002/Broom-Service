<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefundClaim extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'refund_claim';

    protected $fillable = [
        'user_id', 'user_type', 'date', 'amount', 'bill_file', 'status', 'paid_status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
