<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(UserSeeder::class);
        // Call the RequestSeeder to seed requests
        $this->call(RequestFBSeeder::class);

         // Call the FeedbackSeeder to seed feedback data
         $this->call(FeedbackSeeder::class);

         // Call the SubscriptionSeeder to seed subscription data
        $this->call(SubscriptionSeeder::class);

        // Call the DonationSeeder to seed donation data
        $this->call(DonationSeeder::class);
    
    }
}
