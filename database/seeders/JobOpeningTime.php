<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use Carbon\Carbon;

class JobOpeningTime extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jobs = Job::whereNotNull('start_time')->whereNotNull('start_date')->whereNull('job_opening_timestamp')->get();
        foreach ($jobs as $key => $job) {
            $start_date = Carbon::parse($job['start_date'])->format('Y-m-d');
            $opening_time = $start_date . ' ' . $job['start_time'];
            $jobObj = Job::find($job['id']);
            $jobObj->job_opening_timestamp = Carbon::createFromFormat('Y-m-d H:i', $opening_time)->toDateTimeString();
            $jobObj->save();
        }
    }
}
