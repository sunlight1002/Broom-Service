<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHearingInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('hearing_invitations', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('user_id')->constrained()->onDelete('cascade');

        //     $table->foreignId('team_id')
        //         ->nullable()
        //         ->constrained('users')
        //         ->nullOnDelete();

        //     $table->date('start_date');
        //     $table->string('start_time');
        //     $table->string('end_time');
        //     $table->string('meet_via');
        //     $table->string('meet_link')->nullable();
        //     $table->string('purpose')->nullable();
        //     $table->string('booking_status')->nullable();
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hearing_invitations');
    }
}
