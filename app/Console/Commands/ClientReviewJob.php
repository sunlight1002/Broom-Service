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
        $today = Carbon::today()->toDateString();

        $jobs = Job::query()
            ->with(['client', 'jobservice'])
            ->whereHas('worker')
            ->where('status', JobStatusEnum::COMPLETED)
            ->whereDate('completed_at', $today)
            ->where('review_request_sent', false)
            ->get();

        foreach ($jobs as $key => $job) {
            event(new JobReviewRequest($job));
        }

        return 0;
    }
}
