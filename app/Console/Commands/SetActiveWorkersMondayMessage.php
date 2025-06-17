<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SetActiveWorkersMondayMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:active-workers-monday-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'set active workers monday message 1 to 0';

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
        $users = User::where('status', '!=' , 0)->where('stop_last_message', 1)->update(['stop_last_message' => 0]);
        return 0;
    }
}
