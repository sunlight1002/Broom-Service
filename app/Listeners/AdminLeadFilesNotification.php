<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use App\Models\Notification;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

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

            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES,
                "notificationData" => $emailDataWithAdditional
            ]));

            Mail::send('/Mails/AdminLeadFilesMail', $emailDataWithAdditional, function ($messages) use ($scheduleArr, $adminEmail, $filePath) {
                $messages->to($adminEmail);

                $subject = __('mail.meeting.file') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];

                //$messages->attach($filePath);

                $messages->subject($subject);
            });
        }

        // admin bell icon notification
        Notification::create([
            'user_id' => $schedules->client_id,
            'type' => NotificationTypeEnum::FILES,
            'meet_id' => $schedules->id,
            'status' => $schedules->booking_status
        ]);

        //team mail
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::TEAM_LEAD_FILES,
            "notificationData" => $scheduleArr
        ]));
        Mail::send('/Mails/TeamLeadFilesMail', $scheduleArr, function ($messages) use ($scheduleArr, $teamEmail) {
            $messages->to($teamEmail);

            $subject = __('mail.meeting.file') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];

            $messages->subject($subject);
        });
    }
}
