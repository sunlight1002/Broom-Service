<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use App\Models\Notification;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\OfferAccepted;
use App\Events\WhatsappNotificationEvent;

class NotifyForContract implements ShouldQueue
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
     * @param  \App\Events\OfferAccepted  $event
     * @return void
     */
    public function handle(OfferAccepted $event)
    {
        $ofr = $event->offer;

        App::setLocale($ofr['client']['lng']);

        if (isset($ofr['client']) && !empty($ofr['client']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::CONTRACT,
                "notificationData" => $ofr
            ]));
        }

        Mail::send('/Mails/ContractMail', $ofr, function ($messages) use ($ofr) {
            $messages->to($ofr['client']['email']);
            $ofr['client']['lng'] ?
                $sub = __('mail.contract.subject') . "  " . __('mail.contract.company') . " for offer #" . $ofr['id']
                :  $sub = $ofr['id'] . "# " . __('mail.contract.subject') . "  " . __('mail.contract.company');

            $messages->subject($sub);
        });
    }
}
