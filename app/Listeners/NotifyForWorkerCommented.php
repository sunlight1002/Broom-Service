<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use App\Events\WorkerCommented;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForWorkerCommented implements ShouldQueue
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
     * @param  \App\Events\WorkerCommented  $event
     * @return void
     */
    public function handle(WorkerCommented $event)
    {
        Notification::create([
            'user_id' => $event->worker['id'],
            'user_type' => User::class,
            'type' => NotificationTypeEnum::WORKER_COMMENTED,
            'job_id' => $event->job['id'],
            'status' => 'commented'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_COMMENTED,
            "notificationData" => [
                'worker' => $event->worker,
                'job' => $event->job
            ]
        ]));
    }
}
