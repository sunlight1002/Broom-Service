<?php

namespace App\Listeners;

use App\Events\JobWorkerChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToClient;

class SendWorkerChangedNotification implements ShouldQueue
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
     * @param  \App\Events\JobWorkerChanged  $event
     * @return void
     */
    public function handle(JobWorkerChanged $event)
    {
        if (!is_null($event->job['worker']['email']) && $event->job['worker']['email'] != 'Null') {
            App::setLocale($event->job->worker->lng);

            $emailData = array(
                'email' => $event->job['worker']['email'],
                'job' => $event->job->toArray(),
                'start_time' => $event->startTime,
                'content' => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check'),
                'content_data' => __('mail.worker_new_job.new_job_assigned'),
            );
            sendJobWANotification($emailData);
            Mail::send('/Mails/NewJobMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company');
                $messages->subject($sub);
            });
            //send notification to admin
            $adminEmailData = [
                'emailData'   => [
                    'job'   =>  $event->job->toArray(),
                ],
                'emailSubject'  => __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company'),
                'emailTitle'  => __('mail.job_common.new_job_title'),
                'emailContent'  => __('mail.worker_new_job.new_job_assigned') . " " . __('mail.worker_new_job.please_check')
            ];
            event(new JobNotificationToAdmin($adminEmailData));

            //send notification to client
            $jobData = $event->job->toArray();
            $client = $jobData['client'];
            $worker = $jobData['worker'];
            $emailData = [
                'emailSubject'  => __('mail.worker_new_job.subject') . "  " . __('mail.worker_new_job.company'),
                'emailTitle'  => __('mail.job_common.new_job_title'),
                'emailContent'  => __('mail.worker_new_job.new_job_assigned')
            ];
            event(new JobNotificationToClient($worker, $client, $jobData, $emailData));
        }

        if (
            isset($event->oldWorker['email']) &&
            $event->oldWorker['email']
        ) {
            App::setLocale($event->oldWorker['lng']);

            $emailData = array(
                'email' => $event->oldWorker['email'],
                'job' => $event->job->toArray(),
                'old_worker' => $event->oldWorker,
                'old_job' => $event->old_job_data
            );
            if (isset($emailData['old_worker']) && !empty($emailData['old_worker']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_UNASSIGNED,
                    "notificationData" => $emailData
                ]));
            }
            Mail::send('/Mails/WorkerUnassignedMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_unassigned.subject') . "  " . __('mail.worker_unassigned.company');
                $messages->subject($sub);
            });

            //send notification to admin
            $adminEmailData = [
                'emailData'   => [
                    'job'   =>  $event->job->toArray(),
                ],
                'emailSubject'  => __('mail.worker_unassigned.subject') . "  " . __('mail.worker_unassigned.company'),
                'emailTitle'  => 'Job Unassigned',
                'emailContent'  => 'Worker'.$event->oldWorker['firstname'].' '.$event->oldWorker['lastname'].' unassigned from the job #' .$emailData['job']['id']. 'Below are the job details.'
            ];
            event(new JobNotificationToAdmin($adminEmailData));

            //send notification to client
            $jobData = $event->job->toArray();
            $client = $jobData['client'];
            $worker = $event->oldWorker;
            $emailData = [
                'emailSubject'  =>  __('mail.worker_unassigned.subject') . "  " . __('mail.worker_unassigned.company'),
                'emailTitle'  => __('mail.job_common.job_unassigned_title'),
                'emailContent'  =>  __('mail.job_common.admin_change_worker_content', ['workerName' => $event->oldWorker['firstname'].' '.$event->oldWorker['lastname'], 'jobId' => $emailData['job']['id'] ])
            ];
            event(new JobNotificationToClient($worker, $client, $jobData, $emailData));
        }
    }
}
