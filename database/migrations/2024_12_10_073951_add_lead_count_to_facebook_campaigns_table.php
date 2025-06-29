<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeadCountToFacebookCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->integer('lead_count')->default(0)->after('cpp');
            $table->integer('worker_lead_count')->default(0)->after('lead_count');
            $table->integer('worker_count')->default(0)->after('worker_lead_count');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->dropColumn('lead_count');
            $table->dropColumn('worker_lead_count');
            $table->dropColumn('worker_count');
        });
    }
}
