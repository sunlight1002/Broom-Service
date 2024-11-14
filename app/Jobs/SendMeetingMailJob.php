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
        \Log::info($scheduleArr);
        App::setLocale($scheduleArr['client']['lng']);

        $notificationType = $this->schedule->client->notification_type;

        // Handle both email and WhatsApp notifications
        if ($notificationType === 'both') {

            if ($scheduleArr['meet_via'] == "on-site") {

                $Data = [
                    'id' => $scheduleArr['id'],
                    'client' => ['email' => $scheduleArr['client']['email']],
                    'title' => __('mail.label.company_meeting'),
                    'description' => $scheduleArr['purpose'],
                    'location' => $scheduleArr['meet_link'],
                    'start_date' => $scheduleArr['start_date'],
                    'start_time' => $scheduleArr['start_time'],
                    'end_time' => $scheduleArr['end_time'],   
                ];
                
                $icsContent = createIcsFileContent($Data, $scheduleArr['client']['lng']);
                $icsFilePath = tempnam(sys_get_temp_dir(), 'meeting_invite') . '.ics';
                file_put_contents($icsFilePath, $icsContent);
                
                Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr, $icsFilePath) {
                    $messages->to($scheduleArr['client']['email']);
                    $messages->subject(__('mail.meeting.subject', [
                        'id' => $scheduleArr['id']
                    ]));
                    $messages->attach($icsFilePath, [
                        'as' => 'meeting_invite.ics',
                        'mime' => 'text/calendar',
                    ]);
                });
                
                // Delete the temporary file after sending the email
                unlink($icsFilePath);
                

            }else{
                Mail::send('/Mails/OffsiteMeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
                    $messages->to($scheduleArr['client']['email']);
                    $messages->subject(__('mail.meeting.subject', [
                        'id' => $scheduleArr['id']
                    ]));
                });
            }
           

            // Send WhatsApp Notification
            if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone']) && $scheduleArr['meet_via'] == "on-site") {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                    "notificationData" => $scheduleArr
                ]));
            }else{
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                    "notificationData" => $scheduleArr
                ]));
            }
          
           

        } elseif ($notificationType === 'email') {
            // Send Email
            if ($scheduleArr['meet_via'] == "on-site") {

                Mail::send('/Mails/MeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
                    $messages->to($scheduleArr['client']['email']);
                    $messages->subject(__('mail.meeting.subject', [
                        'id' => $scheduleArr['id']
                    ]));
                });

            }else{
                Mail::send('/Mails/OffsiteMeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
                    $messages->to($scheduleArr['client']['email']);
                    $messages->subject(__('mail.meeting.subject', [
                        'id' => $scheduleArr['id']
                    ]));
                });
            }

        } elseif ($notificationType === 'whatsapp') {
            // Send WhatsApp Notification
            if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone']) && $scheduleArr['meet_via'] == "on-site") {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                    "notificationData" => $scheduleArr
                ]));
            }else{
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                    "notificationData" => $scheduleArr
                ]));
            }
        }
        
        // Update the schedule to indicate that the meeting mail has been sent
        $this->schedule->update(['meeting_mail_sent_at' => now()]);
    }
}