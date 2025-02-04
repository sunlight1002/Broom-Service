<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'supplier_id',
        'expense_id',
        'supplier_name',
        'supplier_vat_id',
        'expense_type_id',
        'expense_type_name',
        'expense_docnum',
        'expense_sum',
        'upload_file',
    ];

}
