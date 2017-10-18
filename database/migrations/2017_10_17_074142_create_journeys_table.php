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
            $table->integer('request_id_needer')->references('id')->on('requests')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('user_id_needer')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('request_id_graber')->references('id')->on('requests')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('user_id_graber')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
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
