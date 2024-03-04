<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToClientPropertyAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_property_addresses', function (Blueprint $table) {
            DB::statement("ALTER TABLE client_property_addresses CHANGE is_animal_avail is_dog_avail tinyint(1)");
            $table->boolean('is_cat_avail')->default(false)->after('prefer_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_property_addresses', function (Blueprint $table) {
            $table->dropColumn(['is_cat_avail']);
            DB::statement("ALTER TABLE client_property_addresses CHANGE is_dog_avail is_animal_avail tinyint(1)");
        });
    }
}
