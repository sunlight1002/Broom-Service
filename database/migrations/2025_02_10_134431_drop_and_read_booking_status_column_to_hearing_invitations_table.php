<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAndReadBookingStatusColumnToHearingInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */  
    public function up()
    {
        Schema::table('hearing_invitations', function (Blueprint $table) {
            // Drop booking_status column if it exists
            if (Schema::hasColumn('hearing_invitations', 'booking_status')) {
                $table->dropColumn('booking_status');
            }
        });

        Schema::table('hearing_invitations', function (Blueprint $table) {
            // Add booking_status column again
            $table->string('booking_status')->nullable()->default("pending");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hearing_invitations', function (Blueprint $table) {
            $table->dropColumn('booking_status');
        });
    }
}
