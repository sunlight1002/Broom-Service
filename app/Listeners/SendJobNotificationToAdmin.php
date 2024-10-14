<?php

namespace App\Listeners;

use App\Events\JobNotificationToAdmin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use App\Models\Notification;

class SendJobNotificationToAdmin implements ShouldQueue
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
     * @param  \App\Events\JobNotificationToAdmin  $event
     * @return void
     */
    public function handle(JobNotificationToAdmin $event)
    {
        $adminEmailData = $event->adminEmailData;
        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        App::setLocale('en');
        foreach ($admins as $key => $admin) {
            // if (isset($admin) && !empty($admin['phone'])) {
            //     event(new WhatsappNotificationEvent([
            //         "type" => WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED,
            //         "notificationData" => [$emailData, $admin->toArray()]
            //     ]));
            // }
            // Mail::send('/Mails/admin/JobNotification', [
            //     'data' => $adminEmailData,
            //     'job' => $adminEmailData['emailData']['job'],
            //     'admin' => $admin->toArray()
            // ], function ($messages) use ($admin, $adminEmailData) {
            //     $messages->to($admin['email']);
            //     $messages->subject($adminEmailData['emailSubject']);
            // });
        }
    }
}
