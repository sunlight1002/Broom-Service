<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentJobIdToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {

            $table->unsignedBigInteger('parent_job_id')->nullable()->after('schedule_id');
            $table->foreign('parent_job_id')->references('id')->on('parent_jobs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['parent_job_id']);
            
            // Drop the parent_job_id column
            $table->dropColumn('parent_job_id');
        });
    }
}
