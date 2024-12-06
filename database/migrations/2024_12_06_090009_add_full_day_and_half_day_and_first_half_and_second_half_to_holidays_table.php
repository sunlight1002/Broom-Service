<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFullDayAndHalfDayAndFirstHalfAndSecondHalfToHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->boolean('full_day')->default(false)->after('end_date');
            $table->boolean('half_day')->default(false)->after('full_day');
            $table->boolean('first_half')->default(false)->after('half_day');
            $table->boolean('second_half')->default(false)->after('first_half');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn(['full_day', 'half_day', 'first_half', 'second_half']);
        });
    }
}
