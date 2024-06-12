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
        $jobs = Job::query()
            ->where(function ($q) {
                $q
                    ->whereNull('start_time')
                    ->orWhereNull('end_time');
            })
            ->select('id')
            ->get();

        foreach ($jobs as $key => $job) {
            if (!$job->start_time) {
                $startTime = $job->workerShifts()->orderBy('starting_at', 'asc')->value('starting_at');

                $job->update(['start_time' => Carbon::parse($startTime)->toTimeString()]);
            }

            if (!$job->end_time) {
                $endTime = $job->workerShifts()->orderBy('ending_at', 'desc')->value('ending_at');

                $job->update(['end_time' => Carbon::parse($endTime)->toTimeString()]);
            }
        }
    }
}
