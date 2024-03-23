<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->double('amount', 8, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->dateTime('transaction_at')->nullable();
            $table->string('status', 50);
            $table->string('type', 50)->nullable();
            $table->string('description')->nullable();
            $table->string('source', 50);
            $table->string('destination', 50);
            $table->json('metadata')->nullable();
            $table->string('gateway', 50);
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
        Schema::dropIfExists('transactions');
    }
}
