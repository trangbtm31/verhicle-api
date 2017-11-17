<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpdateJourneysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journeys', function (Blueprint $table) {
            // Rename column
            $table->dropColumn('sender_id');
            $table->renameColumn('user_id_needer', 'sender_id');
            $table->renameColumn('user_id_graber', 'receiver_id');
            $table->renameColumn('request_id_needer', 'sender_request_id');
            $table->renameColumn('request_id_graber', 'receiver_request_id');
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
            $table->dropColumn('user_id_needer');
            $table->dropColumn('request_id_needer');
            $table->dropColumn('user_id_graber');
            $table->dropColumn('request_id_graber');
        });
    }
}
