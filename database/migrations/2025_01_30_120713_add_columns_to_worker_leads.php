<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToWorkerLeads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_leads', function (Blueprint $table) {
            $table->dropColumn('name');

            $table->string('firstname')->nullable()->after('id');
            $table->string('lastname')->nullable()->after('firstname');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->date('renewal_visa')->nullable();
            $table->longText('address')->nullable();
            $table->string('country')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->boolean('is_afraid_by_cat')->default(false);
            $table->boolean('is_afraid_by_dog')->default(false);
            $table->string('visa')->nullable();
            $table->string('passport')->nullable();
            $table->string('passport_card')->nullable();
            $table->string('id_number')->nullable()->after('passport');
            $table->string('id_card')->nullable();
            $table->integer('step')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worker_leads', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');

            $table->dropColumn('firstname');
            $table->dropColumn('lastname');
            $table->dropColumn('gender');
            $table->dropColumn('renewal_visa');
            $table->dropColumn('address');
            $table->dropColumn('country');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('is_afraid_by_cat');
            $table->dropColumn('is_afraid_by_dog');
            $table->dropColumn('visa');
            $table->dropColumn('passport');
            $table->dropColumn('passport_card');
            $table->dropColumn('id_number');
            $table->dropColumn('id_card');
            $table->dropColumn('step');
        });
    }
}
