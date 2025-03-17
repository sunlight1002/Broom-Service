<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GoogleSheetsService;
use App\Services\GoogleSheetsJobSyncService;
use App\Models\Job;

class JobObserverQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobData;

    public function __construct(Job $job)
    {
        $this->jobData = $job;
    }

    public function handle()
    {
        $googleSheetsService = new GoogleSheetsService(); // ✅ Instantiate here
        $syncService = new GoogleSheetsJobSyncService($googleSheetsService);
        $syncService->main($this->jobData);
    }
}


