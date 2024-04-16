<?php

namespace App\Listeners;

use App\Events\WorkerNotApprovedJob;
use App\Models\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

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
            ->get(['name', 'email', 'id']);

        foreach ($admins as $key => $admin) {
            $emailData = array(
                'admin' => $admin->toArray(),
                'email' => $admin->email,
                'job' => $event->job->toArray(),
                'content' => 'Worker has not approved the job yet.'
            );

            Mail::send('/Mails/WorkerJobApprovalMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $messages->subject('Job Not Approved | Broom Service');
            });
        }
    }
}
