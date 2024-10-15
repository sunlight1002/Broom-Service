<?php

namespace App\Console\Commands;

use App\Enums\ContractStatusEnum;
use App\Enums\JobStatusEnum;
use App\Enums\LeadStatusEnum;
use App\Events\ClientLeadStatusChanged;
use App\Models\Client;
use App\Traits\JobSchedule;
use Illuminate\Console\Command;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use Illuminate\Support\Facades\App;
use App\Jobs\SendUninterestedClientEmail;
use Illuminate\Mail\Mailable;


class UpdateClientLeadStatus extends Command
{
    use JobSchedule;

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
            $newLeadStatus = $this->getClientLeadStatusBasedOnJobs($client);

            if (!$client->lead_status || $client->lead_status->lead_status != $newLeadStatus) {
                $client->lead_status()->updateOrCreate(
                    [],
                    ['lead_status' => $newLeadStatus]
                );

                event(new ClientLeadStatusChanged($client, $newLeadStatus));

            }
        }

        return 0;
    }
}
