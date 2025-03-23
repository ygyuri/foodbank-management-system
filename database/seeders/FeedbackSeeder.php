<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\User;
use App\Models\RequestFb;
use App\Models\DonationRequest;
use App\Models\Donation;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Fetch users based on roles
        $foodbanks = User::where('role', 'foodbank')->pluck('id')->toArray();
        $recipients = User::where('role', 'recipient')->pluck('id')->toArray();
        $donors = User::where('role', 'donor')->pluck('id')->toArray();

        // Fetch existing IDs for related tables
        $requestFbs = RequestFb::pluck('id')->toArray();
        $donationRequests = DonationRequest::pluck('id')->toArray();
        $donations = Donation::pluck('id')->toArray();

        // Define scenarios
        $scenarios = [
            ['sender' => $foodbanks, 'receiver' => $recipients, 'type' => 'request_fb'],
            ['sender' => $recipients, 'receiver' => $foodbanks, 'type' => 'request_fb'],
            ['sender' => $foodbanks, 'receiver' => $donors, 'type' => 'donation_request'],
            ['sender' => $donors, 'receiver' => $foodbanks, 'type' => 'donation'],
        ];

        foreach ($scenarios as $scenario) {
            foreach ($scenario['sender'] as $sender_id) {
                foreach ($scenario['receiver'] as $receiver_id) {
                    Feedback::create([
                        'sender_id' => $sender_id,
                        'receiver_id' => $receiver_id,
                        'thank_you_note' => $faker->paragraph,
                        'rating' => $faker->numberBetween(1, 5),
                        'message' => $faker->sentence,
                        'type' => $scenario['type'],
                        
                    ]);
                }
            }
        }
    }
}
