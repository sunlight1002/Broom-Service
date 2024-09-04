<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;


class AdvanceLoanTransaction extends Model implements Auditable
{
    use HasFactory,SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'advance_loan_id',
        'worker_id',
        'type',
        'amount',
        'pending_amount',
        'transaction_date',
    ];

    public function advanceLoan()
    {
        return $this->belongsTo(AdvanceLoan::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
