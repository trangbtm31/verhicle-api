<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            // Add column Delete Date
            $table->increments('id');
            $table->integer('reporter_id')->unsigned();
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('comment');
            $table->integer('reported_user_id')->unsigned();
            $table->foreign('reported_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('journey_id')->unsigned();
            $table->foreign('journey_id')->references('id')->on('journeys')->onDelete('cascade')->onUpdate('cascade');
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
        //
        Schema::dropIfExists('reports');
    }
}
