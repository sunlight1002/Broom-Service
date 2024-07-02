<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->boolean('company')->nullable();
            $table->string('manpower_company_name')->nullable();
            $table->boolean('form_101')->nullable();
            $table->boolean('contact')->nullable();
            $table->boolean('safety')->nullable();
            $table->boolean('insurance')->nullable();
            $table->string('country')->nullable();
            $table->string('visa_id')->nullable();
            $table->string('lng')->nullable();
            $table->boolean('is_invitation_sent')->default(false);
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
        Schema::dropIfExists('worker_invitations');
    }
}
