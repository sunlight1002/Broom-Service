<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\SendWorkerLogin;

class SendWorkerCredentials implements ShouldQueue
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
     * @param  \App\Events\SendWorkerLogin  $event
     * @return void
     */
    public function handle(SendWorkerLogin $event)
    {
        $workerData = array(
            'firstname' => $event->worker['firstname'],
            'lastname' => $event->worker['lastname'],
            'email' => $event->worker['email'],
            'lng' => $event->worker['lng'],
            'passcode' => $event->worker['passcode'],
        );

        // App::setLocale($workerData['lng']);
        // Mail::send('/Mails/WorkerLoginCredentialsMail', $workerData, function ($messages) use ($workerData) {
        //     $messages->to($workerData['email']);

        //     $messages->subject(__('mail.worker_credentials.subject', [
        //         'worker_name' => $workerData['firstname'] . " " . $workerData['lastname']
        //     ]));
        // });
    }
}
