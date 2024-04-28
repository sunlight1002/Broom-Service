<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\ClientPropertyAddress;
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
        $clients = Client::all();
        foreach ($clients as $key => $client) {
            if($contract = $client->contract()->whereIn('status', ['verified', 'un-verified'])->latest()->first()) {
                switch ($contract->status) {
                    case 'un-verified':
                        $leadStatus = LeadStatusEnum::PENDING_CLIENT;
                        break;
                    
                    case 'verified':
                        $leadStatus = LeadStatusEnum::FREEZE_CLIENT;
                        break;
                }
            }
            elseif ($offer = $client->offers()->latest()->first()) {
                switch ($offer->status) {
                    case 'accepted':
                        $leadStatus = LeadStatusEnum::POTENTIAL_CLIENT;
                        break;
                    
                    case 'sent':
                        $leadStatus = LeadStatusEnum::UNANSWERED;
                        break;

                    case 'declined':
                        $leadStatus = LeadStatusEnum::UNINTERESTED;
                        break;
                }
            }
            elseif ($schedule = $client->schedules()->latest()->first()) {
                switch ($schedule->status) {
                    case 'pending':
                    case 'completed':
                    case 'confirmed':
                        $leadStatus = LeadStatusEnum::POTENTIAL_LEAD;
                        break;

                    case 'declined':
                        $leadStatus = LeadStatusEnum::IRRELEVANT;
                        break;
                }
            }
            else {
                $leadStatus = LeadStatusEnum::PENDING_LEAD;
            }

            if($leadStatus) {
                LeadStatus::updateOrCreate(
                    ['client_id' => $client->id],
                    ['lead_status' => $leadStatus]
                );
            }
        }
    }
}
