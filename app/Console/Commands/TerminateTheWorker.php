<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TerminateTheWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terminate:worker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate the worker if their leave date is today';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get today's date
        $today = Carbon::today()->toDateString();

        // Find users whose last_work_date is today and update their status
        $workers = User::whereDate('last_work_date', $today)->get();

        if ($workers->isEmpty()) {
            $this->info('No workers to terminate today.');
        } else {
            foreach ($workers as $worker) {
                $worker->update(['status' => 0]);
                $this->info("Worker ID: {$worker->id}, Name: {$worker->firstname} {$worker->lastname} has been terminated.");
            }
        }

        return 0;
    }
}
