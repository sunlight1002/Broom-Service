<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncExcelSheetAndMakeJob;
use App\Jobs\UpdateExcelSheetWithJobs;
use App\Jobs\SyncGoogleSheetDataJob;


class UpdateGoogleSheetWithJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:job-data-google-sheet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Sheet Data and perform CRUD in sheets as well as database';

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
        dispatch(new UpdateExcelSheetWithJobs())->onConnection('sync');
    }
}
