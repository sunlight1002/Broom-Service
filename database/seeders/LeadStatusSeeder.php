<?php

namespace Database\Seeders;

use App\Enums\ContractStatusEnum;
use App\Enums\JobStatusEnum;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\LeadStatus;
use App\Enums\LeadStatusEnum;

class LeadStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clients = Client::get(['id']);

        foreach ($clients as $key => $client) {
            if ($job = $client->jobs()
                ->whereDate('start_date', '<=', today()->toDateString())
                ->whereDate('start_date', '>', today()->subDays(7)->toDateString())
                ->whereIn('status', [
                    JobStatusEnum::PROGRESS,
                    JobStatusEnum::SCHEDULED,
                    JobStatusEnum::UNSCHEDULED,
                    JobStatusEnum::COMPLETED,
                ])->exists()
            ) {
                $leadStatus = LeadStatusEnum::ACTIVE_CLIENT;
            } else if ($contract = $client->contract()->whereIn('status', ['verified', 'un-verified'])->latest()->first()) {
                switch ($contract->status) {
                    case ContractStatusEnum::VERIFIED:
                        $leadStatus = LeadStatusEnum::FREEZE_CLIENT;
                        break;

                    case ContractStatusEnum::UN_VERIFIED:
                        $leadStatus = LeadStatusEnum::PENDING_CLIENT;
                        break;
                }
            } elseif ($offer = $client->offers()->latest()->first()) {
                switch ($offer->status) {
                    case 'accepted':
                        $leadStatus = LeadStatusEnum::POTENTIAL_CLIENT;
                        break;

                    case 'sent':
                        $leadStatus = LeadStatusEnum::POTENTIAL;
                        break;
                }
            } else {
                $leadStatus = LeadStatusEnum::PENDING;
            }

            if ($leadStatus) {
                LeadStatus::updateOrCreate(
                    ['client_id' => $client->id],
                    ['lead_status' => $leadStatus]
                );
            }
        }
    }
}
