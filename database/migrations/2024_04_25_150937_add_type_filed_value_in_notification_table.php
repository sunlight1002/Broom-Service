<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeFiledValueInNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       \DB::statement("ALTER TABLE `notifications` CHANGE `type` `type` ENUM('sent-meeting','accept-meeting','reject-meeting','accept-offer','reject-offer','contract-accept','contract-reject','client-cancel-job','worker-reschedule','opening-job','reschedule-meeting','files') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `notifications` CHANGE `type` `type` ENUM('sent-meeting','accept-meeting','reject-meeting','accept-offer','reject-offer','contract-accept','contract-reject','client-cancel-job','worker-reschedule','opening-job','reschedule-meeting') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    }
}
