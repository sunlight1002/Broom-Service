<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAmountWithTaxToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `amount` DOUBLE(8, 2) NULL;");
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `paid_amount` DOUBLE(8, 2) NULL;");

        Schema::table('invoices', function (Blueprint $table) {
            $table->double('amount_with_tax', 8, 2)->nullable()->after('paid_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `amount` VARCHAR(255) NOT NULL;");
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `paid_amount` VARCHAR(255) NOT NULL;");

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['amount_with_tax']);
        });
    }
}
