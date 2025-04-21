<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;

class MakeServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:missing-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find all jobs that do not have related jobservices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobs = Job::doesntHave('jobservice')->get();
    
        if ($jobs->isEmpty()) {
            $this->info('All jobs have related jobservice records.');
            return 0;
        }
    
    
        foreach ($jobs as $job) {
            $this->line("Job ID: {$job->id}");
    
            if ($job->parent_job_id) {
                // Find first sibling job with jobservice by parent_job_id
                $parent = Job::with('jobservice')
                    ->where('parent_job_id', $job->parent_job_id)
                    ->whereHas('jobservice')
                    ->orderBy('id', 'asc')
                    ->first();
    
                if ($parent && $parent->jobservice) {
                    $this->info("Found parent-like Job ID: {$parent->id} with jobservice.");
    
                    // Duplicate the jobservice
                    $newJobService = $parent->jobservice->replicate();
                    $newJobService->job_id = $job->id;
                    $newJobService->save();
    
                    $this->info("JobService created for Job ID: {$job->id} using JobService ID: {$parent->jobservice->id}");
                } else {
                    $this->error(" No parent-like job with jobservice found for parent_job_id: {$job->parent_job_id}");
                }
            } else {
                $this->line("↳ No parent job.");
            }
        }
    
        return 0;
    }
}
