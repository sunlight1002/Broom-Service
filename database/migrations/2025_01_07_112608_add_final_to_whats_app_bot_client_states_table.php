<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinalToWhatsAppBotClientStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('whats_app_bot_client_states', function (Blueprint $table) {
            $table->boolean('final')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whats_app_bot_client_states', function (Blueprint $table) {
            $table->dropColumn('final');
        });
    }
}
