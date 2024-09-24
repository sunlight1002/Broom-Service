<?php

namespace App\Jobs;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class SendMeetingNotificationJob implements ShouldQueue
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

        // Send WhatsApp Notification
        if (isset($scheduleArr['client']) && !empty($scheduleArr['client']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::DELETE_MEETING,
                "notificationData" => $scheduleArr
            ]));
        }

        // Send Email Notification
        Mail::send('/Mails/DeleteMeetingMail', $scheduleArr, function ($messages) use ($scheduleArr) {
            $messages->to($scheduleArr['client']['email']);
            $messages->subject(__('mail.cancel_meeting.subject', [
                'id' => $scheduleArr['id']
            ]));
        });
    }
}
