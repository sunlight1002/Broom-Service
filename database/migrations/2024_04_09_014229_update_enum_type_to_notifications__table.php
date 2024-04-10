<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateEnumTypeToNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications_', function (Blueprint $table) {
            DB::statement("ALTER TABLE notifications MODIFY type ENUM('sent-meeting',
            'accept-meeting',
            'reject-meeting',
            'accept-offer',
            'reject-offer',
            'contract-accept',
            'contract-reject',
            'client-cancel-job',
            'worker-reschedule', 'opening-job') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications_', function (Blueprint $table) {
            DB::statement("ALTER TABLE notifications MODIFY type ENUM('sent-meeting',
            'accept-meeting',
            'reject-meeting',
            'accept-offer',
            'reject-offer',
            'contract-accept',
            'contract-reject',
            'client-cancel-job',
            'worker-reschedule') NOT NULL");
        });
    }
}
