<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeForJobInOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `order` MODIFY COLUMN `job_id` VARCHAR(255) NULL AFTER `order_id`");
        DB::statement("ALTER TABLE `order` MODIFY COLUMN `contract_id` BIGINT UNSIGNED NULL AFTER `job_id`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            DB::statement("ALTER TABLE `order` MODIFY COLUMN `job_id` VARCHAR(255) NOT NULL AFTER `order_id`");
            DB::statement("ALTER TABLE `order` MODIFY COLUMN `contract_id` BIGINT UNSIGNED NOT NULL AFTER `job_id`");
        });
    }
}
