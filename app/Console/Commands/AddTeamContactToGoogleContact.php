<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use App\Jobs\AddGoogleContactForTeamJob;

class AddTeamContactToGoogleContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:team-google-contact';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check team contactId and dispatch job to add Google contact if contactId is null';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking teams for missing Google contactId...');

        // Retrieve all teams where contactId is null
        $teams = Admin::whereNull('contactId')->get();

        if ($teams->isEmpty()) {
            $this->info('No teams with null contactId found.');
            return 0;
        }

        foreach ($teams as $team) {
            AddGoogleContactForTeamJob::dispatch($team);
            $this->info("Dispatched AddGoogleContactForTeamJob for team ID: {$team->id}");
        }

        $this->info('All jobs dispatched successfully.');
        return 0;
    }
}
