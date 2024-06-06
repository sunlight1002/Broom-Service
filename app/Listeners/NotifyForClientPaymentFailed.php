<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ClientPaymentFailed;
use App\Events\WhatsappNotificationEvent;
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
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED,
            "notificationData" => [
                'client' => $event->client->toArray(),
                'card' => $event->card->toArray()
            ]
        ]));

        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        App::setLocale('en');
        foreach ($admins as $key => $admin) {
            Notification::create([
                'user_id' => $event->client->id,
                'user_type' => get_class($event->client),
                'type' => NotificationTypeEnum::PAYMENT_FAILED,
                'data' => [
                    'card' => $event->card
                ],
                'status' => 'failed'
            ]);

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
