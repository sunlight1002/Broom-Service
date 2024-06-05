<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WorkerCreated;

class SendWorkerFormsNotification implements ShouldQueue
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
     * @param  \App\Events\WorkerCreated  $event
     * @return void
     */
    public function handle(WorkerCreated $event)
    {
        if (!empty($event->worker->email)) {
            App::setLocale($event->worker->lng);
            $workerArr = $event->worker->toArray();

            Mail::send('/Mails/WorkerForms', $workerArr, function ($messages) use ($workerArr) {
                $messages->to($workerArr['email']);
                ($workerArr['lng'] == 'heb') ?
                    $sub = $workerArr['id'] . "# " . __('mail.forms.worker_forms') :
                    $sub = __('mail.forms.worker_forms') . " #" . $workerArr['id'];
                $messages->subject($sub);
            });
            if (!empty($workerArr['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WORKER_FORMS,
                    "notificationData" => $workerArr
                ]));
            }

            // if (
            //     $event->worker->country == 'Israel' &&
            //     $event->worker->company_type == 'my-company'
            // ) {
            //     if (!empty($workerArr['phone'])) {
            //         event(new WhatsappNotificationEvent([
            //             "type" => WhatsappMessageTemplateEnum::WORKER_CONTRACT,
            //             "notificationData" => $workerArr
            //         ]));
            //         event(new WhatsappNotificationEvent([
            //             "type" => WhatsappMessageTemplateEnum::WORKER_SAFE_GEAR,
            //             "notificationData" => $workerArr
            //         ]));
            //     }

            // Mail::send('/Mails/WorkerContractMail', $workerArr, function ($messages) use ($workerArr) {
            //     $messages->to($workerArr['email']);
            //     ($workerArr['lng'] == 'heb') ?
            //         $sub = $workerArr['id'] . "# " . __('mail.worker_contract.subject') :
            //         $sub = __('mail.worker_contract.subject') . " #" . $workerArr['id'];
            //     $messages->subject($sub);
            // });

            // Mail::send('/Mails/WorkerSafeGearMail', $workerArr, function ($messages) use ($workerArr) {
            //     $messages->to($workerArr['email']);
            //     ($workerArr['lng'] == 'heb') ?
            //         $sub = $workerArr['id'] . "# " . __('mail.worker_safe_gear.subject') :
            //         $sub = __('mail.worker_safe_gear.subject') . " #" . $workerArr['id'];
            //     $messages->subject($sub);
            // });
            // } else if (
            //     $event->worker->country != 'Israel' &&
            //     $event->worker->company_type == 'my-company'
            // ) {
            //     if (!empty($workerArr['phone'])) {
            // event(new WhatsappNotificationEvent([
            //     "type" => WhatsappMessageTemplateEnum::WORKER_SAFE_GEAR,
            //     "notificationData" => $workerArr
            // ]));
            // }

            // Mail::send('Mails.worker.insurance-form', $workerArr, function ($messages) use ($workerArr) {
            //     $messages->to($workerArr['email']);
            //     $messages->subject(__('mail.worker.insurance-form.subject'));
            // });
            // }
        }
    }
}
