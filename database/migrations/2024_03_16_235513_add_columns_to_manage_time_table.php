<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToManageTimeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manage_time', function (Blueprint $table) {
            $table->string('freeze_shift_start_time')->nullable()->after('days');
            $table->string('freeze_shift_end_time')->nullable()->after('freeze_shift_start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manage_time', function (Blueprint $table) {
            $table->dropColumn(['freeze_shift_start_time', 'freeze_shift_end_time']);
        });
    }
}
