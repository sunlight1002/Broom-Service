<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParentJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parent_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('worker_id')->nullable();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('contract_id');
            $table->date('start_date')->nullable();
            $table->enum('status', ['not-started', 'progress', 'completed','scheduled','unscheduled','re-scheduled','cancel'])->default('unscheduled');
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
        Schema::dropIfExists('parent_jobs');
    }
}
