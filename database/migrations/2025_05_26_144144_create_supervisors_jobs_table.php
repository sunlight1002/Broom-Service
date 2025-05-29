<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupervisorsJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supervisors_jobs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('supervisor_id');
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('assigned_by_admin_id')->nullable(); // ✅ declared once and nullable

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('supervisor_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->foreign('assigned_by_admin_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supervisors_jobs');
    }
}
