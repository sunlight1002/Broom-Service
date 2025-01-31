<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleAndHourlyRateToWorkerLeads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_leads', function (Blueprint $table) {
            $table->string('role')->nullable()->after('status');
            $table->string('hourly_rate')->nullable()->after('role');
            $table->string('company_type')->nullable()->after('hourly_rate');
            $table->string('manpower_company_id')->nullable()->after('company_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_leads', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropColumn('hourly_rate');
            $table->dropColumn('company_type');
            $table->dropColumn('manpower_company_id');
        });
    }
}
