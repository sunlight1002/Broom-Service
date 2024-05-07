<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Events\ReScheduleMettingJob;
use App\Models\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class ReScheduleMettingNotification
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
    public function handle(ReScheduleMettingJob $event)
    {
        $schedules = $event->schedules;
        $scheduleArr = $schedules->toArray();
        $teamEmail =$schedules->team['email'];
        $teamId = $schedules->team['id'];
        App::setLocale($scheduleArr['client']['lng']);
        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->where("id",'!=',$teamId)
            ->get(['name', 'email', 'id', 'phone']);
        
        foreach ($admins as $key => $admin) {
            $adminEmail = $admin->email;
             
            $emailDataWithAdditional = array_merge($admin->toArray(), $scheduleArr);
            
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING,
                "notificationData" => $emailDataWithAdditional
            ]));

            Mail::send('/Mails/AdminReScheduleMeetingMail',$emailDataWithAdditional, function ($messages) use ($scheduleArr,$adminEmail) {
                $messages->to($adminEmail);

                $subject = __('mail.meeting.resubject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];
                
                $messages->subject($subject);
            });
        }

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::TEAM_RESCHEDULE_MEETING,
            "notificationData" => $scheduleArr
        ]));

        Mail::send('/Mails/TeamReScheduleMeetingMail', $scheduleArr, function ($messages) use ($scheduleArr,$teamEmail) {
            $messages->to($teamEmail);

            $subject = __('mail.meeting.resubject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];
            
            $messages->subject($subject);
        });

        if (!empty($schedules->start_time) && !empty($schedules->end_time)) {
            Notification::create([
                'user_id' => $schedules->client_id,
                'type' => NotificationTypeEnum::RESCHEDULE_MEETING,
                'meet_id' => $schedules->id,
                'status' => $schedules->booking_status
            ]);
        }
    }
}
