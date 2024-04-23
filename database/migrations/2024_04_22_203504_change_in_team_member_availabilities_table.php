<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeInTeamMemberAvailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('team_member_availabilities', function (Blueprint $table) {
            $table->dropColumn(['time_slots']);
            $table->date('date')->nullable()->after('team_member_id');
            $table->time('start_time')->nullable()->after('date');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('team_member_availabilities', function (Blueprint $table) {
            $table->dropColumn(['date', 'start_time', 'end_time']);
            $table->text('time_slots')->nullable()->after('team_member_id');
        });
    }
}
