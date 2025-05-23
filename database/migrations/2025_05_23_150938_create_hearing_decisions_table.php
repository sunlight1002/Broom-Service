<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHearingDecisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hearing_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('pdf_name');
            $table->string('file');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('admins')->onDelete('cascade');
            $table->foreignId('hearing_invitation_id')->constrained()->onDelete('cascade');
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
        Schema::dropIfExists('hearing_decisions');
    }
}
