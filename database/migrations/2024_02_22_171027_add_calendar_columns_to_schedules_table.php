<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalendarColumnsToSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('is_calendar_event_created')->default(false)->after('meet_link');
            $table->dateTime('meeting_mail_sent_at')->nullable()->after('is_calendar_event_created');
            $table->string('google_calendar_event_id')->nullable()->after('meeting_mail_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['is_calendar_event_created', 'meeting_mail_sent_at', 'google_calendar_event_id']);
        });
    }
}
