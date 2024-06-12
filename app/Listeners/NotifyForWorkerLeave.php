<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Events\WorkerLeaveJob;
use App\Models\Notification;

class NotifyForWorkerLeave implements ShouldQueue
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
     * @param  \App\Events\WorkerLeaveJob  $event
     * @return void
     */
    public function handle(WorkerLeaveJob $event)
    {
        Notification::create([
            'user_id' => $event->worker->id,
            'user_type' => get_class($event->worker),
            'type' => NotificationTypeEnum::WORKER_LEAVES_JOB,
            'status' => 'change',
            'data' => [
                'date' => $event->worker->last_work_date
            ]
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_LEAVES_JOB,
            "notificationData" => [
                'worker' => $event->worker
            ]
        ]));
    }
}
