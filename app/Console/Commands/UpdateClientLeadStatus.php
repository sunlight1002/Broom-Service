<?php

namespace App\Console\Commands;

use App\Enums\ContractStatusEnum;
use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Models\Client;
use Illuminate\Console\Command;

class UpdateClientLeadStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:update-lead-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update client lead status';

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
        $clients = Client::query()
            ->whereHas('contract', function ($q) {
                $q->whereIn('status', [ContractStatusEnum::UN_VERIFIED, ContractStatusEnum::VERIFIED]);
            })
            ->get(['id']);

        foreach ($clients as $key => $client) {
            $isActive = $client->jobs()
                ->whereDate('start_date', '<=', today()->toDateString())
                ->whereDate('start_date', '>', today()->subDays(7)->toDateString())
                ->whereIn('status', [
                    JobStatusEnum::PROGRESS,
                    JobStatusEnum::SCHEDULED,
                    JobStatusEnum::UNSCHEDULED,
                    JobStatusEnum::COMPLETED,
                ])
                ->exists();

            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => $isActive ? LeadStatusEnum::ACTIVE_CLIENT : LeadStatusEnum::FREEZE_CLIENT]
            );
        }

        return 0;
    }
}
