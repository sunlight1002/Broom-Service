<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHearingInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hearing_invitations', function (Blueprint $table) {
            // Drop the existing foreign key constraint for team_id
            $table->dropForeign(['team_id']);
            
            // Modify team_id to reference the admins table instead of users
            $table->foreign('team_id')->references('id')->on('admins')->nullOnDelete();
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
            // Rollback: drop the updated foreign key
            $table->dropForeign(['team_id']);
            
            // Restore the original foreign key to users table
            $table->foreign('team_id')->references('id')->on('users')->nullOnDelete();
        });
    }
}
