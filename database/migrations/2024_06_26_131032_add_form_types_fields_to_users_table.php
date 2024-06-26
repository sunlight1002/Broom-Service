<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormTypesFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('form101')->default(0)->after('is_exist');
            $table->boolean('contract')->default(0)->after('form101');
            $table->boolean('saftey_and_gear')->default(0)->after('contract');
            $table->boolean('insurance')->default(0)->after('saftey_and_gear');
            $table->boolean('is_imported')->default(0)->after('insurance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['form101','contract','saftey_and_gear','insurance','is_imported']);
        });
    }
}
