<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContactPersonNameAndContactPersonPhoneToClientPropertyAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_property_addresses', function (Blueprint $table) {
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_phone')->nullable();
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
            $table->dropColumn('contact_person_name');
            $table->dropColumn('contact_person_phone');
        });
    }
}
