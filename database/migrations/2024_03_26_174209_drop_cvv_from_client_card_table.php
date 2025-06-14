<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCvvFromClientCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_card', function (Blueprint $table) {
            // Drop 'cvv' column
            $table->dropColumn('cvv');

            // Add 'card_holder_id' column after 'card_type'
            $table->string('card_holder_id', 50)->nullable()->after('card_type');

            // Rename 'card_holder' to 'card_holder_name'
            $table->renameColumn('card_holder', 'card_holder_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_card', function (Blueprint $table) {
            // Rename 'card_holder_name' back to 'card_holder'
            $table->renameColumn('card_holder_name', 'card_holder');

            // Add 'cvv' column after 'valid'
            $table->longText('cvv')->nullable()->after('valid');

            // Drop 'card_holder_id'
            $table->dropColumn('card_holder_id');
        });
    }
}
