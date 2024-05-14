<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeCommentTypeInJobCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_comments', function (Blueprint $table) {
            DB::statement("ALTER TABLE `job_comments` MODIFY COLUMN `comment` text NULL;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_comments', function (Blueprint $table) {
            DB::statement("ALTER TABLE `job_comments` MODIFY COLUMN `comment` text NOT NULL;");
        });
    }
}
