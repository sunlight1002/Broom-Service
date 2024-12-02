<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancesLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advances_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users');
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->decimal('monthly_payment', 10, 2)->nullable();
            $table->date('loan_start_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('advances_loans');
        Schema::enableForeignKeyConstraints();
    }
    
}
