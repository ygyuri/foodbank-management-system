<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequestFB;
use App\Models\User;  // Assuming User model is used for foodbanks
use Faker\Factory as Faker;

class RequestFBSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Get a random foodbank user to associate with requests
        $foodbanks = User::where('role', 'foodbank')->pluck('id');  // Assuming 'role' defines the foodbank role

        foreach ($foodbanks as $foodbank_id) {
            RequestFB::create([
                'foodbank_id' => $foodbank_id,
                'type' => $faker->randomElement(['food', 'supplies', 'monetary']),
                'quantity' => $faker->numberBetween(1, 100),
            ]);
        }
    }
}