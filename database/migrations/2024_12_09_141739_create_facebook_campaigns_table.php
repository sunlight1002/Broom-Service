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
            $table->string('campaign_name')->nullable();
            $table->string('campaign_id')->unique();
            $table->string('date_start')->nullable();
            $table->string('date_stop')->nullable();
            $table->decimal('spend', 10, 2)->default(0);
            $table->integer('reach')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('cpc', 10, 2)->nullable();
            $table->decimal('cpm', 10, 2)->nullable();
            $table->decimal('ctr', 10, 2)->nullable();
            $table->decimal('cpp', 10, 2)->nullable();
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
