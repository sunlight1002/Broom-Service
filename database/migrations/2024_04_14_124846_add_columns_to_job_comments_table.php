<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        // Step 1: Rename the column
        Schema::table('job_comments', function (Blueprint $table) {
            $table->renameColumn('role', 'comment_for');
        });

        // Step 2: Add new columns after renaming is complete
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
        // Step 1: Drop new columns
        Schema::table('job_comments', function (Blueprint $table) {
            $table->dropColumn(['commenter_type', 'commenter_id']);
        });

        // Step 2: Rename column back
        Schema::table('job_comments', function (Blueprint $table) {
            $table->renameColumn('comment_for', 'role');
        });
    }
}
