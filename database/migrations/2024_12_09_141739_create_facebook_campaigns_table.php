<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacebookCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facebook_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('campaign_id'); // Facebook campaign ID
            $table->string('date_start');
            $table->string('date_stop');
            $table->decimal('spend', 10, 2);
            $table->integer('reach');
            $table->integer('clicks');
            $table->decimal('cpc', 10, 2);
            $table->decimal('cpm', 10, 2);
            $table->decimal('ctr', 10, 2);
            $table->decimal('cpp', 10, 2);
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
        Schema::dropIfExists('facebook_campaigns');
    }
}
