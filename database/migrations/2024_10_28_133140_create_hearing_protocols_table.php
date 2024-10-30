<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHearingProtocolsTable extends Migration
{
    public function up()
    {
        Schema::create('hearing_protocols', function (Blueprint $table) {
            $table->id();
            $table->string('pdf_name');
            $table->string('file');
            $table->unsignedBigInteger('worker_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('hearing_invitation_id')->nullable()->index();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('worker_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('hearing_invitation_id')->references('id')->on('hearing_invitations')->onDelete('cascade'); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('hearing_protocols');
    }
}
