<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVoiceBotCallDateAndVoiceBotCallTimeToLeadActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_activities', function (Blueprint $table) {
            $table->date('voice_bot_call_date')->nullable()->after('reschedule_time');
            $table->time('voice_bot_call_time')->nullable()->after('voice_bot_call_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_activities', function (Blueprint $table) {
            $table->dropColumn('voice_bot_call_date');
            $table->dropColumn('voice_bot_call_time');
        });
    }
}
