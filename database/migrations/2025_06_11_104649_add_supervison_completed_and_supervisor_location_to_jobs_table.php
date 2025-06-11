<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupervisonCompletedAndSupervisorLocationToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->boolean('supervison_completed')->default(false);
            $table->string('supervisor_location')->nullable();
            $table->string('supervisor_lat')->nullable();
            $table->string('supervisor_lng')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('supervison_completed');
            $table->dropColumn('supervisor_location');
            $table->dropColumn('supervisor_lat');
            $table->dropColumn('supervisor_lng');
        });
    }
}


