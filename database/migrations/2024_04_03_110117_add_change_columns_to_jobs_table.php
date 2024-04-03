<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChangeColumnsToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_worker_id')->nullable()->after('is_one_time_job');
            $table->date('previous_worker_after')->nullable()->after('previous_worker_id');
            $table->text('previous_shifts')->nullable()->after('previous_worker_after');
            $table->date('previous_shifts_after')->nullable()->after('previous_shifts');
            $table->string('cancelled_for', 30)->nullable()->after('cancelled_at');
            $table->date('cancel_until_date')->nullable()->after('cancelled_for');
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
            $table->dropColumn(['previous_worker_id', 'previous_worker_after', 'previous_shifts', 'previous_shifts_after', 'cancelled_for', 'cancel_until_date']);
        });
    }
}
