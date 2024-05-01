<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOriginalNameToAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `attachments` RENAME COLUMN `file` TO `file_name`;");

        Schema::table('attachments', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('file_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('original_name');
        });

        DB::statement("ALTER TABLE `attachments` RENAME COLUMN `file_name` TO `file`;");
    }
}
