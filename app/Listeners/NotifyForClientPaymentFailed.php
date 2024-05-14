<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientPaymentFailed;
use App\Events\WhatsappNotificationEvent;
use App\Mail\Admin\Form101SignedMail as AdminForm101SignedMail;
use App\Mail\Worker\Form101SignedMail;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class NotifyForClientPaymentFailed implements ShouldQueue
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
     * @param  \App\Events\ClientPaymentFailed  $event
     * @return void
     */
    public function handle(ClientPaymentFailed $event)
    {
        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        App::setLocale('en');
        foreach ($admins as $key => $admin) {
            // Notification::create([
            //     'user_id' => $event->client->id,
            //     'type' => NotificationTypeEnum::PAYMENT_FAILED,
            //     'job_id' => $event->job->id,
            //     'status' => 'reschedule'
            // ]);

            // if (isset($data['admin']) && !empty($data['admin']['phone'])) {
            //     event(new WhatsappNotificationEvent([
            //         "type" => WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION,
            //         "notificationData" => $data
            //     ]));
            // }

            $emailData = [
                'client' => $event->client,
                'card' => $event->card,
                'admin' => $admin
            ];

            Mail::send('Mails.admin.client-payment-failed', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['admin']['email']);
                $sub = __('mail.admin.client-payment-failed.subject');
                $messages->subject($sub);
            });
        }

        $emailData = [
            'client' => $event->client,
            'card' => $event->card,
        ];

        App::setLocale($event->client->lng);

        Mail::send('Mails.client.payment-failed', $emailData, function ($messages) use ($emailData) {
            $messages->to($emailData['client']['email']);
            $sub = __('mail.client.payment-failed.subject');
            $messages->subject($sub);
        });
    }
}
