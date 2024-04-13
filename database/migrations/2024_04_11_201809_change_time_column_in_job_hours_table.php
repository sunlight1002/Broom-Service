<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeTimeColumnInJobHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `job_hours` MODIFY COLUMN `start_time` DATETIME NULL AFTER `worker_id`");
        DB::statement("ALTER TABLE `job_hours` MODIFY COLUMN `end_time` DATETIME NULL AFTER `start_time`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `job_hours` MODIFY COLUMN `start_time` VARCHAR(255) NULL AFTER `worker_id`");
        DB::statement("ALTER TABLE `job_hours` MODIFY COLUMN `end_time` VARCHAR(255) NULL AFTER `start_time`");
    }
}
