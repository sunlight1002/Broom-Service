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

        $teamEmail = $schedules->team['email'];
        $teamId = $schedules->team['id'];

        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->where("id", '!=', $teamId)
            ->get(['name', 'email', 'id', 'phone']);

        $fileName = $event->files->file;
        $filePath = asset('storage/uploads/ClientFiles') . "/" . $fileName;

        //admin mail's
        foreach ($admins as $key => $admin) {
            $adminEmail = $admin->email;

            $emailDataWithAdditional = array_merge($admin->toArray(), $scheduleArr);
            $emailDataWithAdditional['file_name'] = $fileName;

            Mail::send('/Mails/AdminLeadFilesMail', $emailDataWithAdditional, function ($messages) use ($scheduleArr, $adminEmail, $filePath) {
                $messages->to($adminEmail);

                $messages->attach($filePath);

                $messages->subject(__('mail.meeting.file_subject', [
                    'id' => $scheduleArr['id']
                ]));
            });
        }

        $scheduleArr['file_name'] = $fileName;
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

        //team mail
        Mail::send('/Mails/TeamLeadFilesMail', $scheduleArr, function ($messages) use ($scheduleArr, $teamEmail, $filePath) {
            $messages->to($teamEmail);

            $messages->attach($filePath);
            $messages->subject(__('mail.meeting.file_subject', [
                'id' => $scheduleArr['id']
            ]));
        });
    }
}
