<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeJobHourColumnInJobServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `job_services` MODIFY COLUMN `job_hour` VARCHAR(255) NULL AFTER `name`");

        Schema::table('job_services', function (Blueprint $table) {
            $table->integer('duration_minutes')->nullable()->after('job_hour');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `job_services` MODIFY COLUMN `job_hour` VARCHAR(255) NOT NULL AFTER `name`");

        Schema::table('job_services', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
}
