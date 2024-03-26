<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnsFromContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['name_on_card', 'cvv', 'card_type', 'card_sign', 'start_date']);
            $table->unsignedBigInteger('card_id')->nullable()->after('unique_hash');
            $table->foreign('card_id')->references('id')->on('client_card')->nullOnDelete();
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
            $table->dropForeign(['card_id']);
            $table->dropColumn(['card_id']);
            $table->string('name_on_card')->nullable()->after('additional_address');
            $table->string('cvv')->nullable()->after('name_on_card');
            $table->string('card_type')->nullable()->after('cvv');
            $table->longText('card_sign')->nullable()->after('card_type');
            $table->date('start_date')->nullable()->after('status');
        });
    }
}
