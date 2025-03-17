<?php

namespace App\Observers;

use App\Models\Job;
use App\Jobs\JobObserverQueue;

class JobObserver
{
    public function __construct()
    {

    }

    public function created(Job $job)
    {
        if (Job::$skipObserver) {
            \Log::info('Observer skipped');
            return;
        }
        \Log::info('Observer fired');
        dispatch(new JobObserverQueue($job));
    }

    public function updated(Job $job)
    {
        if (Job::$skipObserver) {
            \Log::info('Observer updated');
            return;
        }
        \Log::info('Observer fired');
        dispatch(new JobObserverQueue($job));
    }
}


