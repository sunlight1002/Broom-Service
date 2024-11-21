<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class NotifyNonClientsOrHisNoPriceOffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:non-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify non-clients or clients with no price offer or unsigned contracts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clientNumbers = [
            // '1234567890',
            // '917665655655',
            // '943433434343343',
            '565665555565665'
        ];

        foreach ($clientNumbers as $index => $number) {
            $client = Client::where('phone', $number)->first();

            if (!$client) {
                // Client does not exist in the system
                $this->sendMessage1($number, $index, 'client does not exist');
                continue;
            }

            // Check if client has any offer with status 'sent' or 'accepted'
            $offerExists = $client->offers()
                ->whereIn('status', ['sent', 'accepted'])
                ->exists();
            Log::info(['offerExists' => $offerExists]);

            if (!$offerExists) {
                // Client exists but has no price offer
                $this->sendMessage1($number, $index, 'Client exists but has no price offer.');
                continue;
            }

            // Check if client has a contract or an unsigned contract
            $hasNoContracts = !$client->contract()->exists();
            $hasUnsignedContracts = $client->contract()
                ->where('status', 'not-signed')
                ->exists();

            Log::info(['hasNoContracts' => $hasNoContracts]);
            Log::info(["hasUnsignedContracts" => $hasUnsignedContracts]);

            if (($hasNoContracts || $hasUnsignedContracts)) {
                // Client has a price offer but no contract or an unsigned contract
                $this->sendMessage2($number, $index, 'Client has an offer but no signed contract or an unsigned contract.');
            }
        }

        $this->info('Notifications sent successfully.');
        return 0;
    }

    /**
     * Send message to a client number.
     *
     * @param string $number
     * @param string $message
     */
    private function sendMessage1($number, $index, $message)
    {
        Log::info("Sending message1 to $number: $message");
        $data = [
            'id' => $index,
            'lng' => 'heb',
            'phone'=> $number,
            'disable_notification' => 0
        ];

        // event(new WhatsappNotificationEvent([
        //     "type" => WhatsappMessageTemplateEnum::CLIENT_NOT_IN_SYSTEM_OR_NO_OFFER,
        //     "notificationData" => [
        //         'client' => $data,
        //     ]
        // ]));
    }

    private function sendMessage2($number, $index, $message)
    {
        Log::info("Sending message2 to $number: $message");
        $data = [
            'id' => $index,
            'lng' => 'heb',
            'phone'=> $number,
            'disable_notification' => 0
        ];

        // event(new WhatsappNotificationEvent([
        //     "type" => WhatsappMessageTemplateEnum::CLIENT_HAS_OFFER_BUT_NO_SIGNED_OR_NO_CONTRACT,
        //     "notificationData" => [
        //         'client' => $data,
        //     ]
        // ]));
    }
}
