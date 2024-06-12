<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\AdminCommented;
use App\Events\WhatsappNotificationEvent;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class NotifyForAdminCommented implements ShouldQueue
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
     * @param  \App\Events\AdminCommented  $event
     * @return void
     */
    public function handle(AdminCommented $event)
    {
        Notification::create([
            'user_id' => $event->admin['id'],
            'user_type' => Admin::class,
            'type' => NotificationTypeEnum::ADMIN_COMMENTED,
            'job_id' => $event->job['id'],
            'status' => 'commented'
        ]);

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::ADMIN_COMMENTED,
            "notificationData" => [
                'admin' => $event->admin,
                'job' => $event->job
            ]
        ]));
    }
}
