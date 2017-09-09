<?php
use Illuminate\Database\Seeder;

class OAuthClientSeeder extends Seeder {

    public function run(){

        DB::table('oauth_clients')->delete();

        for ($i=0; $i < 10; $i++){

            DB::table('oauth_clients')->insert(
                [   'id' => "id$i",
                    'secret' => "secret$i",
                    'name' => "Test Client $i"
                ]
            );
        }
    }
}