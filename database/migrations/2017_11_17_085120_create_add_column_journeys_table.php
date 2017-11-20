<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddColumnJourneysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journeys', function (Blueprint $table) {
            // Add column Delete Date
            $table->dateTime('delete_at')->nullable();
            $table->dateTime('finish_at')->nullable();
            $table->integer('user_delete_id')->nullable();
            $table->float('rating_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('journeys', function (Blueprint $table) {
            //
            $table->dropColumn('delete_at');
            $table->dropColumn('rating_value');
            $table->dropColumn('finish_at');
            $table->dropColumn('user_delete_id');
        });
    }
}
