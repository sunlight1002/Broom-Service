<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminTimeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_time_logs', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to admins table
            $table->unsignedBigInteger('admins_id');
            $table->foreign('admins_id')->references('id')->on('admins')->onDelete('cascade');

            // Timer fields
            $table->timestamp('start_timer')->nullable();
            $table->timestamp('end_timer')->nullable();

            // Location fields
            $table->string('start_location')->nullable();
            $table->string('end_location')->nullable();
            $table->string('start_lat')->nullable();
            $table->string('start_lng')->nullable();
            $table->string('end_lat')->nullable();
            $table->string('end_lng')->nullable();

            // Difference in minutes
            $table->integer('difference_minutes')->nullable();

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
        Schema::dropIfExists('admin_time_logs');
    }
}
