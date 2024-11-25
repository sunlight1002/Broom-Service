<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\AddGoogleContactForWorkerJob;

class AddWorkerContactToGoogleContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:worker-google-contact';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check worker contactId and dispatch job to add Google contact if contactId is null';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking workers for missing Google contactId...');

        // Retrieve all workers where contactId is null
        $workers = User::whereNull('contactId')->get();

        if ($workers->isEmpty()) {
            $this->info('No workers with null contactId found.');
            return 0;
        }

        foreach ($workers as $worker) {
            AddGoogleContactForWorkerJob::dispatch($worker);
            $this->info("Dispatched AddGoogleContactForWorkerJob for worker ID: {$worker->id}");
        }

        $this->info('All jobs dispatched successfully.');
        return 0;
    }
}
