<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoustomMessageSendTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_message_send', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status');
            $table->longText('message_en')->nullable();
            $table->longText('message_heb')->nullable();
            $table->longText('message_ru')->nullable();
            $table->longText('message_spa')->nullable();
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
        Schema::dropIfExists('coustom_message_send');
    }
}
