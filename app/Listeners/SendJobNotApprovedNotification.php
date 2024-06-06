<?php

namespace App\Listeners;

use App\Events\WorkerNotApprovedJob;
use App\Models\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToWorker;

class SendJobNotApprovedNotification implements ShouldQueue
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
     * @param  \App\Events\WorkerNotApprovedJob  $event
     * @return void
     */
    public function handle(WorkerNotApprovedJob $event)
    {
        App::setLocale('en');
        //send notification to admin
        $adminEmailData = [
            'emailData'   => [
                'job' => $event->job->toArray(),
            ],
            'emailSubject'  => 'Job Not Approved | Broom Service',
            'emailTitle'  => 'Worker Not Approved Job',
            'emailContent'  => 'Worker has not approved the job yet.'
        ];
        event(new JobNotificationToAdmin($adminEmailData));

        //send notification to worker
        $job = $event->job->toArray();
        $worker = $job['worker'];
        App::setLocale($worker['lng']);

        $emailData = [
            'emailSubject'  => __('mail.job_common.not_approve_subject'),
            'emailTitle'  => __('mail.job_common.not_approve_title'),
            'emailContent'  => __('mail.job_common.not_approve_content')
        ];
        event(new JobNotificationToWorker($worker, $job, $emailData));

        //old
        // $admins = Admin::query()
        //     ->where('role', 'admin')
        //     ->whereNotNull('email')
        //     ->get(['name', 'email', 'id', 'phone']);

        // App::setLocale('en');
        // foreach ($admins as $key => $admin) {
        //     $emailData = array(
        //         'admin' => $admin->toArray(),
        //         'email' => $admin->email,
        //         'job' => $event->job->toArray(),
        //         'content' => 'Worker has not approved the job yet.'
        //     );
        //     // Mail::send('/Mails/WorkerJobApprovalMail', $emailData, function ($messages) use ($emailData) {
        //     //     $messages->to($emailData['email']);
        //     //     $messages->subject('Job Not Approved | Broom Service');
        //     // });
        // }

        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::WORKER_JOB_NOT_APPROVAL,
            "notificationData" => array(
                'job' => $event->job->toArray(),
                'content' => 'Worker has not approved the job yet.'
            )
        ]));
    }
}
