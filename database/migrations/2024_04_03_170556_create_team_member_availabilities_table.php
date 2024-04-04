<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamMemberAvailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_member_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_member_id')->nullable();
            $table->foreign('team_member_id')->references('id')->on('admins')->onDelete('cascade');
            $table->text('time_slots')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_member_availabilities');
    }
}
