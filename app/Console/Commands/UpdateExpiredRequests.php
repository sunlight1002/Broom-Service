<?php

namespace App\Console\Commands;

use App\Enums\ChangeWorkerRequestStatusEnum;
use App\Models\ChangeJobWorkerRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateExpiredRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the expired requests';

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
        ChangeJobWorkerRequest::query()
            ->where('status', ChangeWorkerRequestStatusEnum::PENDING)
            ->whereDate('date', '<', Carbon::today()->toDateString())
            ->update([
                'status' => ChangeWorkerRequestStatusEnum::REJECTED
            ]);

        return 0;
    }
}
