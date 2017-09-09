<?php

use Illuminate\Database\Seeder;

use App\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checking because truncate() will fail
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        User::truncate();
        factory(User::class, 10)->create();
        // Enable it back
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        $this->call('OAuthClientSeeder');
        // $this->call('UsersTableSeeder');
    }
}
