<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_id')->nullable();
            $table->integer('expense_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->integer('supplier_vat_id')->nullable();
            $table->integer('expense_type_id')->nullable();
            $table->string('expense_type_name')->nullable();
            $table->string('expense_docnum')->nullable();
            $table->string('expense_sum')->nullable();
            $table->longText('upload_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expenses');
    }
}
