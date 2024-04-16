<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddColumnsToJobCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `job_comments` RENAME COLUMN `role` TO `comment_for`;");

        Schema::table('job_comments', function (Blueprint $table) {
            $table->string('commenter_type')->nullable()->after('comment_for');
            $table->unsignedBigInteger('commenter_id')->nullable()->after('commenter_type');
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
            $table->dropColumn(['commenter_type', 'commenter_id']);
        });

        DB::statement("ALTER TABLE `job_comments` RENAME COLUMN `comment_for` TO `role`;");
    }
}
