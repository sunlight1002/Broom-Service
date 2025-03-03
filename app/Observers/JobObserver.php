<?php

namespace App\Observers;

use App\Models\Job;
use App\Services\GoogleSheetsService;
use App\Services\GoogleSheetsJobSyncService;

class JobObserver
{
    protected $syncService;

    public function __construct()
    {
        $googleSheetsService = new GoogleSheetsService();
        $this->syncService = new GoogleSheetsJobSyncService($googleSheetsService);
    }

    public function created(Job $job)
    {
        $this->syncService->syncJob($job);
    }

    public function updated(Job $job)
    {
        $this->syncService->syncJob($job);
    }
}
