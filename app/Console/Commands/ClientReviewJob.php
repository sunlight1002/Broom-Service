<?php

namespace App\Console\Commands;

use App\Enums\JobStatusEnum;
use App\Events\JobReviewRequest;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClientReviewJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:review-job-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify client about reviewing job';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get yesterday's date and day of the week
        $yesterday = Carbon::yesterday();
        $dayOfWeek = $yesterday->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 5 = Friday
        \Log::info('Review request for jobs completed on: ' . $yesterday->toDateString());
    
        // Fetch jobs completed yesterday
        $jobs = Job::query()
            ->with(['client', 'jobservice'])
            ->whereHas('worker')
            ->where('status', JobStatusEnum::COMPLETED)
            ->whereDate('completed_at', $yesterday->toDateString())
            ->where('review_request_sent', false)
            ->get();
    
        // Notify clients about the review request
        foreach ($jobs as $job) {
            // Check if the job was completed on a Friday
            if ($dayOfWeek === Carbon::FRIDAY) {
                // Log the info that review requests will be sent on Sunday
                \Log::info('Job completed on Friday. Review request will be sent on Sunday for job ID: ' . $job->id);
            } else {
                // If the job was not completed on Friday, send the review request
                event(new JobReviewRequest($job));
            }
        }
    
        // Now handle jobs completed on Friday, to send requests on Sunday
        if ($dayOfWeek === Carbon::SUNDAY) {
            $fridayJobs = Job::query()
                ->with(['client', 'jobservice'])
                ->whereHas('worker')
                ->where('status', JobStatusEnum::COMPLETED)
                ->whereDate('completed_at', $yesterday->subDay()->toDateString()) // Check for jobs completed on Friday
                ->where('review_request_sent', false)
                ->get();
    
            foreach ($fridayJobs as $job) {
                event(new JobReviewRequest($job));
            }
        }
    
        return 0;
    }
    
}
