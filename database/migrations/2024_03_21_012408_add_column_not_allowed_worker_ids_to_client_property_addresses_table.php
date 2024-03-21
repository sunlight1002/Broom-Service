<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnNotAllowedWorkerIdsToClientPropertyAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_property_addresses', function (Blueprint $table) {
            $table->string('not_allowed_worker_ids')->nullable()->after('is_dog_avail');
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
            $table->dropColumn(['not_allowed_worker_ids']);
        });
    }
}
