<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_management_id')->constrained('task_management')->onDelete('cascade'); 
            $table->unsignedBigInteger('assignable_id');
            $table->string('assignable_type');
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
        Schema::dropIfExists('task_workers');
    }
}
