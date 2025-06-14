<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('invoices', function (Blueprint $table) {
            // Change job_id to nullable and move after invoice_id
            $table->string('job_id', 255)->nullable()->change(); // `after` is not supported here

            // Rename customer to client_id (requires doctrine/dbal)
            $table->renameColumn('customer', 'client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('job_id', 255)->nullable(false)->change();
            $table->renameColumn('client_id', 'customer');
        });
    }
}
