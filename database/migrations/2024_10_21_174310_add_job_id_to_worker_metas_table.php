<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobIdToWorkerMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_metas', function (Blueprint $table) {
            $table->integer('job_id')->nullable()->after('worker_id'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_metas', function (Blueprint $table) {
            $table->dropColumn('job_id'); // Drop the columns on rollback
        });
    }
}
