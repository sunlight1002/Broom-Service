<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\SendClientLogin;

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
     * @param  \App\Events\SendClientLogin  $event
     * @return void
     */
    public function handle(SendClientLogin $event)
    {
        $client = $event->client;

        // App::setLocale($client['lng']);
        // Mail::send('/Mails/ClientLoginCredentialsMail', $client, function ($messages) use ($client) {
        //     $messages->to($client['email']);

        //     $messages->subject(__('mail.client_credentials.subject', [
        //         'client_name' => $client['firstname'] . " " . $client['lastname']
        //     ]));
        // });
    }
}
