<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirstDateToWorkerInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_invitations', function (Blueprint $table) {
            $table->date('first_date')->nullable();
            $table->string('role')->nullable();
            $table->string('payment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_invitations', function (Blueprint $table) {
            $table->dropColumn(['first_date', 'role', 'payment']);
        });
    }
}
