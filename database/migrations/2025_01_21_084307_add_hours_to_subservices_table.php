<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHoursToSubservicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subservices', function (Blueprint $table) {
            $table->string('hours')->nullable()->after('apartment_size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subservices', function (Blueprint $table) {
            $table->dropColumn('hours');
        });
    }
}
