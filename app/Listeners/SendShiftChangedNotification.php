<?php

namespace App\Listeners;

use App\Events\JobShiftChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToClient;

class SendShiftChangedNotification implements ShouldQueue
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
        if (
            $event->job['worker'] &&
            !is_null($event->job['worker']['email']) &&
            $event->job['worker']['email'] != 'Null'
        ) {
            App::setLocale($event->job->worker->lng);

            $emailData = array(
                'email' => $event->job['worker']['email'],
                'job' => $event->job->toArray(),
                'start_time' => $event->startTime,
                'content' => __('mail.worker_job.shift_changed') . " " . __('mail.worker_new_job.please_check'),
                'content_data' => __('mail.worker_job.shift_changed'),
            );
            sendJobWANotification($emailData);
            // Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
            //     $messages->to($emailData['email']);
            //     $sub = __('mail.worker_job.shift_changed_subject');
            //     $messages->subject($sub);
            // });
        }

        App::setLocale('en');
        //send notification to admin
        $adminEmailData = [
            'emailData'   => [
                'job'   =>  $event->job->toArray(),
            ],
            'emailSubject'  => __('mail.worker_job.shift_changed_subject'),
            'emailTitle'  => 'New Job',
            'emailContent'  => __('mail.worker_job.shift_changed') . " " . __('mail.worker_new_job.please_check')
        ];
        event(new JobNotificationToAdmin($adminEmailData));

        //send notification to client
        $jobData = $event->job->toArray();
        $client = $jobData['client'];
        $worker = $jobData['worker'];
        App::setLocale($client['lng']);

        $emailData = [
            'emailSubject'  => __('mail.worker_job.shift_changed_subject'),
            'emailTitle'  => __('mail.job_common.new_job_title'),
            'emailContent'  => __('mail.worker_job.shift_changed')
        ];
        event(new JobNotificationToClient($worker, $client, $jobData, $emailData));
    }
}
