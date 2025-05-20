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
use Illuminate\Support\Facades\Log;

class SendMeetingNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scheduleArr;
    protected $schedule;

    /**
     * Create a new job instance.
     *
     * @param array $scheduleArr
     */
    public function __construct(array $scheduleArr)
    {
        $this->scheduleArr = $scheduleArr;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $scheduleArr = $this->scheduleArr;
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
            $messages->bcc(config('services.mail.default'));
            $messages->subject(__('mail.cancel_meeting.subject', [
                'id' => $scheduleArr['id']
            ]));
        });
    }

    /**
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::error('Error sending meeting notification for schedule ID ' . $this->schedule->id . ': ' . $exception->getMessage());
    }
}
