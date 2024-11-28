<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Jobs\AddGoogleContactJob;

class AddClientContactToGoogleContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:client-google-contact';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check client contactId and dispatch job to add Google contact if contactId is null';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking clients for missing Google contactId...');

        // Retrieve all clients where contactId is null
        $clients = Client::whereNull('contactId')->get();

        if ($clients->isEmpty()) {
            $this->info('No clients with null contactId found.');
            return 0;
        }

        foreach ($clients as $client) {
            AddGoogleContactJob::dispatch($client);
            $this->info("Dispatched AddGoogleContactJob for client ID: {$client->id}");
        }

        $this->info('All jobs dispatched successfully.');
        return 0;
    }
}
