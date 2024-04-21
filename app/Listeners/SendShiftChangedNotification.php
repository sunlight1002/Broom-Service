<?php

namespace App\Listeners;

use App\Events\JobShiftChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class SendShiftChangedNotification
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
     * @param  \App\Events\JobShiftChanged  $event
     * @return void
     */
    public function handle(JobShiftChanged $event)
    {
        if (!is_null($event->job['worker']['email']) && $event->job['worker']['email'] != 'Null') {
            App::setLocale($event->job->worker->lng);

            $emailData = array(
                'email' => $event->job['worker']['email'],
                'job' => $event->job->toArray(),
                'start_time' => $event->startTime,
                'content' => __('mail.worker_job.shift_changed') . " " . __('mail.worker_new_job.please_check'),
            );

            Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_job.shift_changed_subject');
                $messages->subject($sub);
            });
        }
    }
}
