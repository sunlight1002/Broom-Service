<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterDefaultStatusInUsersTable extends Migration
{
    public function up()
    {
        // Change default value to 0 using raw SQL
        DB::statement("ALTER TABLE users MODIFY status INT DEFAULT 2");
    }

    public function down()
    {
        // Revert default value back to 1
        DB::statement("ALTER TABLE users MODIFY status INT DEFAULT 1");
    }
}
