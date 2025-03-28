<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConflictsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conflicts', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('job_id');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients');
        
            $table->unsignedBigInteger('worker_id');
            $table->foreign('worker_id')->references('id')->on('users');

            $table->unsignedBigInteger('conflict_client_id')->default(null);
            $table->unsignedBigInteger('conflict_job_id')->default(null);
        
            $table->date('date');
            $table->string('shift');
            $table->string('hours');
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
        Schema::dropIfExists('conflicts');
    }
}
