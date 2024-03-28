<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderIdToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['rate']);
            $table->double('total_amount', 8, 2)->after('comment');
            $table->unsignedBigInteger('order_id')->nullable()->after('total_amount');
            $table->foreign('order_id')->references('id')->on('order')->nullOnDelete();
            $table->boolean('is_order_generated')->default(false)->after('order_id');
            $table->unsignedBigInteger('invoice_id')->nullable()->after('isOrdered');
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->boolean('is_invoice_generated')->default(false)->after('invoice_id');
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
            $table->dropForeign(['order_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['total_amount', 'order_id', 'is_order_generated', 'invoice_id', 'is_invoice_generated']);
            $table->unsignedBigInteger('rate')->nullable()->after('comment');
        });
    }
}
