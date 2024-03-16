<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancellationColumnsToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->double('cancellation_fee_percentage', 8, 2)->nullable()->after('is_next_job_created');
            $table->double('cancellation_fee_amount', 8, 2)->nullable()->after('cancellation_fee_percentage');
            $table->string('cancelled_by_role')->nullable()->after('cancellation_fee_amount');
            $table->dateTime('cancelled_at')->nullable()->after('cancelled_by_role');
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
            $table->dropColumn(['cancellation_fee_percentage', 'cancellation_fee_amount', 'cancelled_by_role', 'cancelled_at']);
        });
    }
}
