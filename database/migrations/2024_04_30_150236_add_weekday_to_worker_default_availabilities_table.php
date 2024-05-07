<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeekdayToWorkerDefaultAvailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_default_availabilities', function (Blueprint $table) {
            $table->unsignedInteger('weekday')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_default_availabilities', function (Blueprint $table) {
            $table->dropColumn(['weekday']);
        });
    }
}
