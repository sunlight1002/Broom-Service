<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class OldNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Notification::whereNull('user_type')->update([
            'user_type' => Client::class
        ]);
    }
}
