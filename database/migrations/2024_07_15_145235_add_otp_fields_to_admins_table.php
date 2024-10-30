<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtpFieldsToAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(true);
            $table->string('otp')->nullable();
            $table->timestamp('otp_expiry')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('otp');
            $table->dropColumn('otp_expiry');
        });
    }
}
