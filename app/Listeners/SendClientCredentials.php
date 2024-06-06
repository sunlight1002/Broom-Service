<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\ContractSigned;

class SendClientCredentials implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ContractSigned  $event
     * @return void
     */
    public function handle(ContractSigned $event)
    {
        $contract = $event->contract;
        $client = $event->client;

        App::setLocale($client['lng']);
        Mail::send('/Mails/ClientLoginCredentialsMail', $client->toArray(), function ($messages) use ($contract, $client) {
            $messages->to($client['email']);
            $client['lng'] ?
                $sub = __('mail.client_credentials.credentials') . "  " . __('mail.contract.company') . " of client #" . $client['firstname'] . " " . $client['lastname']
                :  $sub = $client['firstname'] . " " . $client['lastname'] . "# " . __('mail.client_credentials.credentials') . "  " . __('mail.contract.company');

            $messages->subject($sub);
        });
    }
}
