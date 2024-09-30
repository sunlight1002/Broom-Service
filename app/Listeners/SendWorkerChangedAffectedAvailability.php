<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Events\WorkerChangeAffectedAvailability;
use App\Models\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Notification;

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
        Notification::create([
            'user_id' => $event->worker->id,
            'user_type' => get_class($event->worker),
            'type' => NotificationTypeEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB,
            'status' => 'changed',
            'data' => [
                'date' => $event->date,
            ]
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB,
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

            // Mail::send('Mails.admin.worker-availability-changed', $emailData, function ($messages) use ($emailData) {
            //     $messages->to($emailData['email']);
            //     $messages->subject(__('mail.worker_re_scheduled.header'));
            // });
        }
    }
}
