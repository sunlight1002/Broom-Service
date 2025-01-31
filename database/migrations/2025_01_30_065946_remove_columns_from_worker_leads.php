<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveColumnsFromWorkerLeads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worker_leads', function (Blueprint $table) {
            $table->dropColumn([
                'ready_to_get_best_job', 
                'ready_to_work_in_house_cleaning', 
                'areas_aviv_herzliya_ramat_gan_kiryat_ono_good',
                'none_id_visa',
                'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm',
                'full_or_part_time'
            ]);
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
            $table->dropColumn([
                'ready_to_get_best_job', 
                'ready_to_work_in_house_cleaning', 
                'areas_aviv_herzliya_ramat_gan_kiryat_ono_good',
                'none_id_visa',
                'work_sunday_to_thursday_fit_schedule_8_10am_12_2pm',
                'full_or_part_time'
            ]);
        });
    }
}
