<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResIdAndWaIdColumnToWebhookResponses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webhook_responses', function (Blueprint $table) {
            $table->string('res_id')->nullable(); // Changed default(null) to nullable()
            $table->integer('wa_id')->nullable(); // Changed default(null) to nullable()
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webhook_responses', function (Blueprint $table) {
            $table->dropColumn(['res_id', 'wa_id']); // Drop the columns on rollback
        });
    }
}
