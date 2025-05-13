<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToWebhookResponsesTable extends Migration
{
    public function up()
    {
        Schema::table('webhook_responses', function (Blueprint $table) {
            $table->index(['number', 'read']);   // Composite index
            $table->index('number');             // Single column index (optional if composite is used)
            $table->index('created_at');         // Useful for sorting recent messages
        });
    }

    public function down()
    {
        Schema::table('webhook_responses', function (Blueprint $table) {
            $table->dropIndex(['number', 'read']);
            $table->dropIndex(['number']);
            $table->dropIndex(['created_at']);
        });
    }
}
