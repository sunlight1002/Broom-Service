<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountToOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->double('amount', 8, 2)->nullable()->after('paid_status');
            $table->double('amount_with_tax', 8, 2)->nullable()->after('amount');
            $table->double('paid_amount', 8, 2)->nullable()->after('amount_with_tax');
            $table->double('unpaid_amount', 8, 2)->nullable()->after('paid_amount');
            $table->boolean('is_force_closed')->default(false)->after('unpaid_amount');
            $table->dateTime('force_closed_at')->nullable()->after('is_force_closed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn(['amount', 'paid_amount', 'unpaid_amount', 'is_force_closed', 'force_closed_at']);
        });
    }
}
