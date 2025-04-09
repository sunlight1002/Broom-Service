<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discount', function (Blueprint $table) {
            $table->id();
            $table->longText('client_ids')->nullable();
            $table->longText('service_ids')->nullable();
            $table->longText('days')->nullable(); 
            $table->string('type')->nullable(); 
            $table->float('value')->nullable(); 
            $table->longText('applied_client_ids')->nullable();
            // $table->float('amount')->nullable(); 
            // $table->float('percentage')->nullable();
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
        Schema::dropIfExists('discount');
    }
}
