<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHearingInvitationIdToClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedBigInteger('hearing_invitation_id')->nullable()->index();
            $table->foreign('hearing_invitation_id')->references('id')->on('hearing_invitations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['hearing_invitation_id']);
            $table->dropColumn('hearing_invitation_id');
        });
    }
}
