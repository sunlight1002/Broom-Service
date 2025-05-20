<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WorkerForm101Requested;

class SendWorkerForm101Notification implements ShouldQueue
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
     * @param  \App\Events\WorkerForm101Requested  $event
     * @return void
     */
    public function handle(WorkerForm101Requested $event)
    {
        if (!empty($event->worker->email)) {
            App::setLocale($event->worker->lng);
            $workerArr = $event->worker->toArray();
            $workerArr['formId'] = $event->formID;

            Mail::send('/Mails/Form101Mail', $workerArr, function ($messages) use ($workerArr) {
                $messages->to($workerArr['email']);
                $messages->bcc(config('services.mail.default'));
                $messages->subject(__('mail.form_101.subject', [
                    'id' => $workerArr['id']
                ]));
            });

            if (!empty($workerArr['phone'])) {
                event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::FORM101,
                    "notificationData" => [
                        'worker' => $workerArr
                    ]
                ]));
            }
        }
    }
}
