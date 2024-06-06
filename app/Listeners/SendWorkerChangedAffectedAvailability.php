<?php

namespace App\Listeners;

use App\Events\WorkerChangeAffectedAvailability;
use App\Models\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class SendWorkerChangedAffectedAvailability implements ShouldQueue
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
     * @param  \App\Events\WorkerChangeAffectedAvailability  $event
     * @return void
     */
    public function handle(WorkerChangeAffectedAvailability $event)
    {
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_AVAILABILITY_CHANGED,
            "notificationData" => array(
                'worker' => $event->worker->toArray(),
                'date' => $event->date,
                'affectedAvailability' => $event->affectedAvailability,
            )
        ]));

        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        App::setLocale('en');
        foreach ($admins as $key => $admin) {
            $emailData = array(
                'admin' => $admin->toArray(),
                'email' => $admin->email,
                'worker' => $event->worker->toArray(),
                'date' => $event->date,
                'affectedAvailability' => $event->affectedAvailability,
            );

            Mail::send('Mails.admin.worker-availability-changed', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $messages->subject('Worker Re-scheduled Availability | Broom Service');
            });
        }
    }
}
