<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Events\AdminReScheduleMettingJob;
use App\Models\Admin;

class AdminReScheduleMettingNotification
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
        dd($schedules);
        $scheduleArr = $schedules->toArray();
        $teamEmail =$schedules->team['email'];
        $teamId = $schedules->team['id'];
        App::setLocale($scheduleArr['client']['lng']);
        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->where("id",'!=',$teamId)
            ->get(['name', 'email', 'id', 'phone']);
        
        //admin mail's
        foreach ($admins as $key => $admin) {
            $adminEmail = $admin->email;
                
            $emailDataWithAdditional = array_merge($admin->toArray(), $scheduleArr);
            
            Mail::send('/Mails/AdminReScheduleMeetingMail',$emailDataWithAdditional, function ($messages) use ($scheduleArr,$adminEmail) {
                $messages->to($adminEmail);

                $subject = __('mail.meeting.resubject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];
                
                $messages->subject($subject);
            });
        }

        // admin bell icon notification
        if (!empty($schedules->start_time) && !empty($schedules->end_time)) {
            Notification::create([
                'user_id' => $schedules->client_id,
                'type' => 'reschedule-meeting',
                'meet_id' => $schedules->id,
                'status' => $schedules->booking_status
            ]);
        }

        //team mail
        Mail::send('/Mails/TeamReScheduleMeetingMail', $scheduleArr, function ($messages) use ($scheduleArr,$teamEmail) {
            $messages->to($teamEmail);

            $subject = __('mail.meeting.resubject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];
            
            $messages->subject($subject);
        });

        //customer mail
        Mail::send('/Mails/ClientReScheduleMeetingMail', $scheduleArr, function ($messages) use ($scheduleArr,$teamEmail) {
            $messages->to($teamEmail);
            
            if ($scheduleArr['client']['lng'] == 'en') {
                $subject = __('mail.meeting.subject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company') . " #" . $scheduleArr['id'];
            } else {
                $subject = $scheduleArr['id'] . "# " . __('mail.meeting.subject') . " " . __('mail.meeting.from') . " " . __('mail.meeting.company');
            }

            $messages->subject($subject);
        });
    }
}
