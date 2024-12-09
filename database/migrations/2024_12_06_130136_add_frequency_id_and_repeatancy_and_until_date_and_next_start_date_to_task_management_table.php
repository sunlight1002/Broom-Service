<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFrequencyIdAndRepeatancyAndUntilDateAndNextStartDateToTaskManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_management', function (Blueprint $table) {
            $table->unsignedBigInteger('frequency_id')->nullable()->after('due_date'); // Foreign key
            $table->foreign('frequency_id')->references('id')->on('service_schedules')->onDelete('set null'); // Foreign key constraint
            $table->string('repeatancy')->nullable()->after('frequency_id');
            $table->date('until_date')->nullable()->after('repeatancy');
            $table->date('next_start_date')->nullable()->after('until_date');
        });
    }

    public function down()
    {
        Schema::table('task_management', function (Blueprint $table) {
            $table->dropForeign(['frequency_id']); // Drop the foreign key first
            $table->dropColumn(['frequency_id', 'repeatancy', 'until_date', 'next_start_date']);
        });
    }
}
