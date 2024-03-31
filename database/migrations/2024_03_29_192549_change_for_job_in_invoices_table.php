<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeForJobInInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `job_id` VARCHAR(255) NULL AFTER `invoice_id`");
        DB::statement("ALTER TABLE `invoices` RENAME COLUMN `customer` TO `client_id`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `job_id` VARCHAR(255) NOT NULL AFTER `invoice_id`");
        DB::statement("ALTER TABLE `invoices` RENAME COLUMN `client_id` TO `customer`;");
    }
}
