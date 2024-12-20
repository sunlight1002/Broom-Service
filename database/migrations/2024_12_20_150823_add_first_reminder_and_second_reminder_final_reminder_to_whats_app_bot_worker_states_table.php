<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirstReminderAndSecondReminderFinalReminderToWhatsAppBotWorkerStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('whats_app_bot_worker_states', function (Blueprint $table) {
            $table->boolean('first_reminder')->default(false);
            $table->boolean('second_reminder')->default(false);
            $table->boolean('final_reminder')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whats_app_bot_worker_states', function (Blueprint $table) {
            $table->dropColumn('first_reminder');
            $table->dropColumn('second_reminder');
            $table->dropColumn('final_reminder');
        });
    }
}
