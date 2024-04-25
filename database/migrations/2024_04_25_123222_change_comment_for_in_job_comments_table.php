<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeCommentForInJobCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `job_comments` MODIFY COLUMN `comment_for` enum( 'client', 'worker', 'admin' ) NOT NULL AFTER `name`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `job_comments` MODIFY COLUMN `comment_for` enum( 'client', 'worker' ) NOT NULL AFTER `name`;");
    }
}
