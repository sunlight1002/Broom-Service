<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoImageAndContactColumnToWebhookResponses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webhook_responses', function (Blueprint $table) {
            $table->string('video')->nullable(); 
            $table->string('image')->nullable();
            $table->string('contact')->nullable();
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
            $table->dropColumn(['video', 'image', 'contact']); // Drop the columns on rollback
        });
    }
}
