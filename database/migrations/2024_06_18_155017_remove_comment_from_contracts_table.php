<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCommentFromContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->json('form_data')->nullable()->after('consent_to_ads');
            $table->dateTime('signed_at')->nullable()->after('form_data');
            $table->dropColumn(['comment']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('consent_to_ads');
            $table->dropColumn(['form_data', 'signed_at']);
        });
    }
}
