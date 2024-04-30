<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeekdayToTeamMemberDefaultAvailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('team_member_default_availabilities', function (Blueprint $table) {
            $table->unsignedInteger('weekday')->after('team_member_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('team_member_default_availabilities', function (Blueprint $table) {
            $table->dropColumn(['weekday']);
        });
    }
}
