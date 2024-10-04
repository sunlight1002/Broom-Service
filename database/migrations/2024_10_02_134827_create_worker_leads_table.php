<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkerLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worker_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('ready_to_get_best_job')->default(false); 
            $table->boolean('ready_to_work_in_house_cleaning')->default(false); 
            $table->boolean('experience_in_house_cleaning')->default(false); 
            $table->boolean('areas_aviv_herzliya_ramat_gan_kiryat_ono_good')->default(false); 
            $table->string('none_id_visa')->nullable(); 
            $table->boolean('you_have_valid_work_visa')->default(false); 
            $table->boolean('work_sunday_to_thursday_fit_schedule_8_10am_12_2pm')->default(false); 
            $table->string('full_or_part_time')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worker_leads');
    }
}

