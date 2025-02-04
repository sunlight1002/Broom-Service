<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSomeNotificationsColumnsToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('review_notification')->default(false);
            $table->boolean('monday_notification')->default(false);
            $table->boolean('wednesday_notification')->default(false);
            $table->boolean('s_bot_notification')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('review_notification');
            $table->dropColumn('monday_notification');
            $table->dropColumn('wednesday_notification');
            $table->dropColumn('s_bot_notification');
        });
    }
}
