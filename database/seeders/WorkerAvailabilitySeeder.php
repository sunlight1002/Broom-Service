<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\WorkerAvailability;

class WorkerAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $workerAvailabilities = WorkerAvailability::all();
        foreach ($workerAvailabilities as $key => $workerAvailability) {
            $workerAvailability->update(['start_time' => "08:00:00", 'end_time' => "16:00:00"]);
        }
    }
}
