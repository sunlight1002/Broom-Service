<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Services\GoogleSheetsService;
use App\Services\GoogleSheetsJobSyncService;

class ObserverTest extends Command
{
    protected $syncService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $googleSheetsService = new GoogleSheetsService();
        $this->syncService = new GoogleSheetsJobSyncService($googleSheetsService);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
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
