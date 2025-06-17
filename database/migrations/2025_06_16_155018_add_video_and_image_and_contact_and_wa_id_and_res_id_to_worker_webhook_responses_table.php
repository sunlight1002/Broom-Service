<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoAndImageAndContactAndWaIdAndResIdToWorkerWebhookResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_webhook_responses', function (Blueprint $table) {
            $table->string('video')->nullable();
            $table->string('image')->nullable();
            $table->string('contact')->nullable();
            $table->string('wa_id')->nullable();
            $table->string('res_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_webhook_responses', function (Blueprint $table) {
            $table->dropColumn('video');
            $table->dropColumn('image');
            $table->dropColumn('contact');
            $table->dropColumn('wa_id');
            $table->dropColumn('res_id');
        });
    }
}
