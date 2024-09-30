<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
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
        logger($ofr);
        $notificationType = $ofr['client']['notification_type'];

        App::setLocale($ofr['client']['lng']);
        if ($notificationType === 'both') {
            if (isset($ofr['client']) && !empty($ofr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CONTRACT,
                    "notificationData" => $ofr
                ]));
            }

            Mail::send('/Mails/ContractMail', $ofr, function ($messages) use ($ofr) {
                $messages->to($ofr['client']['email']);

                $messages->subject(__('mail.contract.subject', [
                    'id' => $ofr['id']
                ]));
            });
        }elseif ($notificationType === 'email') {
            \Log::info("accepted");
            Mail::send('/Mails/ContractMail', $ofr, function ($messages) use ($ofr) {
                $messages->to($ofr['client']['email']);

                $messages->subject(__('mail.contract.subject', [
                    'id' => $ofr['id']
                ]));
            });
        }else{
            if (isset($ofr['client']) && !empty($ofr['client']['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::CONTRACT,
                    "notificationData" => $ofr
                ]));
            }
        }

    }
}
