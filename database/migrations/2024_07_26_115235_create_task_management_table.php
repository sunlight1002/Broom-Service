<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_management', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->foreignId('user_id');
            $table->foreignId('phase_id')->constrained('phase')->onDelete('cascade');
            $table->string('task_name');
            $table->string('status');
            $table->string('priority');
            $table->date('due_date')->nullable();
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
        Schema::dropIfExists('task_management');
    }
}
