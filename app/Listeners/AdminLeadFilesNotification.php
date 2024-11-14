<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use App\Models\Notification;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Client;

class AdminLeadFilesNotification implements ShouldQueue
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $schedules = $event->schedules;
        $scheduleArr = $schedules->toArray();
        App::setLocale($scheduleArr['client']['lng']);

        $fileName = $event->files->file;

        $scheduleArr['file_name'] = $fileName;
        $scheduleArr['file_upload_date'] = $event->files->created_at->format('d-m-Y H:i');
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES,
            "notificationData" => $scheduleArr
        ]));

        // admin bell icon notification
        Notification::create([
            'user_id' => $schedules->client_id,
            'user_type' => Client::class,
            'type' => NotificationTypeEnum::FILES,
            'meet_id' => $schedules->id,
            'status' => $schedules->booking_status
        ]);
    }
}
