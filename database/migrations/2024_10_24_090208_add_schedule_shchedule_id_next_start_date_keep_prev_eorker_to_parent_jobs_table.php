<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScheduleShcheduleIdNextStartDateKeepPrevEorkerToParentJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parent_jobs', function (Blueprint $table) {
            $table->dateTime('next_start_date')->nullable()->after('status');
            $table->boolean('keep_prev_worker')->default(false);
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('schedule')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parent_jobs', function (Blueprint $table) {
            $table->dropColumn(['next_start_date','keep_prev_worker','schedule_id','schedule']);

        });
    }
}
