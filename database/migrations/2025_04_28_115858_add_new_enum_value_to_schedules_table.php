<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewEnumValueToSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add 'rescheduled' to the 'booking_status' enum in 'schedules' table if not exists
        $enumColumn = DB::selectOne("SHOW COLUMNS FROM schedules WHERE Field = 'booking_status'");

        if ($enumColumn && strpos($enumColumn->Type, "'rescheduled'") === false) {
            // Extract current enum values
            preg_match('/enum\((.*)\)/', $enumColumn->Type, $matches);
            $currentValues = array_map(fn($v) => trim($v, "'"), explode(',', $matches[1]));

            // Add the new enum value
            $currentValues[] = 'rescheduled';

            // Rebuild ENUM string
            $newEnum = "'" . implode("','", $currentValues) . "'";

            // Alter the column
            DB::statement("ALTER TABLE schedules MODIFY booking_status ENUM($newEnum) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove 'rescheduled' from the 'booking_status' enum in 'schedules' table if exists
        $enumColumn = DB::selectOne("SHOW COLUMNS FROM schedules WHERE Field = 'booking_status'");

        if ($enumColumn && strpos($enumColumn->Type, "'rescheduled'") !== false) {
            // Extract existing enum values
            preg_match('/enum\((.*)\)/', $enumColumn->Type, $matches);
            $currentValues = array_filter(array_map(fn($v) => trim($v, "'"), explode(',', $matches[1])), function($v) {
                return $v !== 'rescheduled';
            });

            // Rebuild ENUM string
            $newEnum = "'" . implode("','", $currentValues) . "'";

            // Alter the column
            DB::statement("ALTER TABLE schedules MODIFY booking_status ENUM($newEnum) NOT NULL");
        }
    }
}
