<?php

namespace App\Jobs;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Schedule;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;


class SendMeetingMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $schedule;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Schedule $schedule
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $scheduleArr = $this->schedule->toArray();
        App::setLocale($scheduleArr['client']['lng']);

        $notificationType = $this->schedule->client->notification_type;

        // Handle both email and WhatsApp notifications
        if ($notificationType === 'both') {

            // Uncomment the following lines if you want to send an email
            Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
                $messages->to($scheduleArr['client']['email']);
                $messages->subject(__('mail.meeting.subject', [
                    'id' => $scheduleArr['id']
                ]));
            });

            // Send WhatsApp Notification
            if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                    "notificationData" => $scheduleArr
                ]));
            }

           

        } elseif ($notificationType === 'email') {
            // Send Email
            Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
                $messages->to($scheduleArr['client']['email']);
                $messages->subject(__('mail.meeting.subject', [
                    'id' => $scheduleArr['id']
                ]));
            });

        } elseif ($notificationType === 'whatsapp') {
            // Send WhatsApp Notification
            if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                    "notificationData" => $scheduleArr
                ]));
            }
        }
        
        // Update the schedule to indicate that the meeting mail has been sent
        $this->schedule->update(['meeting_mail_sent_at' => now()]);
    }
}