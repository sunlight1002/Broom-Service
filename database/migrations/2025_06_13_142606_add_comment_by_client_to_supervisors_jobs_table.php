<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentByClientToSupervisorsJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supervisors_jobs', function (Blueprint $table) {
            $table->string('comment_by_client')->nullable()->after('assigned_by_admin_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('supervisors_jobs', function (Blueprint $table) {
            $table->dropColumn('comment_by_client');
        });
    }
}
