<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvanceLoan extends Model implements Auditable
{
    use HasFactory,SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'advances_loans';

    protected $fillable = ['worker_id', 'type','amount','monthly_payment','loan_start_date','status'];


    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function transactions()
    {
        return $this->hasMany(AdvanceLoanTransaction::class);
    }
}
