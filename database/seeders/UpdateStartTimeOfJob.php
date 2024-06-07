<?php

namespace Database\Seeders;

use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UpdateStartTimeOfJob extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jobs = Job::whereNull('start_time')->select('id')->get();


        foreach ($jobs as $key => $job) {
            $startTime = $job->workerShifts()->orderBy('starting_at', 'asc')->value('starting_at');

            $job->update(['start_time' => Carbon::parse($startTime)->toTimeString()]);
        }
    }
}
