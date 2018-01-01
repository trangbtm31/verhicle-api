<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJourneysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('journeys', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('request_hiker_id')->unsigned();
            $table->integer('hiker_id')->unsigned();
            $table->integer('request_driver_id')->unsigned();
            $table->integer('driver_id')->unsigned();
            $table->foreign('request_hiker_id')->references('id')->on('requests')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('hiker_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('request_driver_id')->references('id')->on('requests')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('status');
            $table->integer('sender_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('journeys');
        //
    }
}
