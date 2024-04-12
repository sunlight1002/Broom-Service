<?php

namespace App\Console\Commands;

use App\Events\WorkerNotApprovedJob;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class WorkerFailedToApproveJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:failed-to-approve-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify admin about worker not approved job';

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
        $tomorrow = Carbon::tomorrow()->toDateString();

        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            ->where('is_worker_reminded', true)
            ->whereNull('worker_approved_at')
            ->whereDate('start_date', $tomorrow)
            ->get();

        foreach ($jobs as $key => $job) {
            event(new WorkerNotApprovedJob($job));
        }

        return 0;
    }
}
