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

class SendJobNotApprovedNotification
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
        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);
        
        foreach ($admins as $key => $admin) {
            $emailData = array(
                'admin' => $admin->toArray(),
                'email' => $admin->email,
                'job' => $event->job->toArray(),
                'content' => 'Worker has not approved the job yet.'
            );
            if (isset($emailData['admin']) && !empty($emailData['admin']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_JOB_NOT_APPROVAL,
                    "notificationData" => $emailData
                ]));
            }
            Mail::send('/Mails/WorkerJobApprovalMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $messages->subject('Job Not Approved | Broom Service');
            });
        }
    }
}
