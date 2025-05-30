<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncExcelSheetAndMakeJob;
use App\Jobs\UpdateExcelSheetWithJobs;
use App\Jobs\SyncGoogleSheetDataJob;


class AddJobsInGoogleSheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:jobs-in-google-sheet {start_date} {end_date}';


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
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');
        // \Log::info($startDate." - ". $endDate);
        dispatch(new SyncExcelSheetAndMakeJob(null, $startDate, $endDate))->onConnection('sync');
    }
}
