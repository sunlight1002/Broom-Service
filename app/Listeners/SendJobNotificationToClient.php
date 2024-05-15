<?php

namespace App\Listeners;

use App\Events\JobNotificationToClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendJobNotificationToClient implements ShouldQueue
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
     * @param  \App\Events\JobNotificationToClient  $event
     * @return void
     */
    public function handle(JobNotificationToClient $event)
    {
        $worker = $event->worker;
        $client = $event->client;
        $job = $event->job;
        $emailData = $event->emailData;
        App::setLocale($client['lng']);
        Mail::send('/Mails/client/JobNotification', ['job' => $job,'worker' =>  $worker, 'emailData' => $emailData, 'client' => $client], function ($messages) use ($client, $emailData) {
            $messages->to($client['email']);
            $messages->subject($emailData['emailSubject']);
        });
    }
}
