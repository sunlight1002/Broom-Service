<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreezeToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->time('freeze_shift_start_time')->nullable()->after('is_afraid_by_dog');
            $table->time('freeze_shift_end_time')->nullable()->after('freeze_shift_start_time');
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
            $table->dropColumn(['freeze_shift_start_time', 'freeze_shift_end_time']);
        });
    }
}
