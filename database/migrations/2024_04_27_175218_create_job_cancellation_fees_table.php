<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobCancellationFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_cancellation_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->boolean('is_paid')->default(false);
            $table->double('cancellation_fee_percentage', 8, 2)->nullable();
            $table->double('cancellation_fee_amount', 8, 2)->nullable();
            $table->string('cancelled_user_role');
            $table->unsignedBigInteger('cancelled_by');
            $table->string('duration', 30)->nullable();
            $table->date('until_date')->nullable();
            $table->string('action', 30)->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('order')->nullOnDelete();
            $table->boolean('is_order_generated')->default(false);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->boolean('is_invoice_generated')->default(false);
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
        Schema::dropIfExists('job_cancellation_fees');
    }
}
