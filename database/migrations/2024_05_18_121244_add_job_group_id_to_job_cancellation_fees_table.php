<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobGroupIdToJobCancellationFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_cancellation_fees', function (Blueprint $table) {
            $table->unsignedBigInteger('job_group_id')->nullable()->after('job_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_cancellation_fees', function (Blueprint $table) {
            $table->dropColumn(['job_group_id']);
        });
    }
}
