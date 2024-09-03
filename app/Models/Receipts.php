<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipts extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'receipt';

    protected $fillable = [
        'invoice_id',
        'invoice_icount_id',
        'receipt_id',
        'docurl'
    ];
}
