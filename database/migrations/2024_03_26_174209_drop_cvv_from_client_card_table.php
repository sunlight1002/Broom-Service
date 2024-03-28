<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->dropColumn(['cvv']);
            $table->string('card_holder_id', 50)->nullable()->after('card_type');
        });

        DB::statement("ALTER TABLE `client_card` RENAME COLUMN `card_holder` TO `card_holder_name`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `client_card` RENAME COLUMN `card_holder_name` TO `card_holder`;");

        Schema::table('client_card', function (Blueprint $table) {
            $table->longText('cvv')->nullable()->after('valid');
            $table->dropColumn(['card_holder_id']);
        });
    }
}
