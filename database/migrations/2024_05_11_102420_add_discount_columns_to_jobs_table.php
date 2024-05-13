<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountColumnsToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->double('subtotal_amount', 8, 2)->nullable()->after('comment');
            $table->string('discount_type', 10)->nullable()->after('subtotal_amount');
            $table->double('discount_value', 8, 2)->nullable()->after('discount_type');
            $table->double('discount_amount', 8, 2)->nullable()->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'discount_type', 'discount_value', 'discount_amount']);
        });
    }
}
