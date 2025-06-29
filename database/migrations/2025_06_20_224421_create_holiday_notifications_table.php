<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidayNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('holiday_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained('holidays')->onDelete('cascade');
            $table->string('notification_type'); // 'two_weeks_before', 'one_week_before', etc.
            $table->timestamp('sent_at');
            $table->timestamps();
            
            // Prevent duplicate notifications for the same holiday and type
            $table->unique(['holiday_id', 'notification_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('holiday_notifications');
    }
}
