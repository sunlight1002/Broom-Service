<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        // Step 1: Rename the column
        Schema::table('attachments', function (Blueprint $table) {
            $table->renameColumn('file', 'file_name');
        });

        // Step 2: Add new column after renaming is complete
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
        // Step 1: Drop the added column
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('original_name');
        });

        // Step 2: Rename column back
        Schema::table('attachments', function (Blueprint $table) {
            $table->renameColumn('file_name', 'file');
        });
    }
}
