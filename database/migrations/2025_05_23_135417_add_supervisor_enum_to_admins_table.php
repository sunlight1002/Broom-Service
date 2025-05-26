<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSupervisorEnumToAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('superadmin', 'admin', 'member', 'hr', 'supervisor') NOT NULL DEFAULT 'member'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('superadmin', 'admin', 'member', 'hr') NOT NULL DEFAULT 'member'");
    }
}
