<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WorkerCreated;
use App\Models\Admin;

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
        $admin = Admin::where('role', 'hr')->first();
        App::setLocale($event->worker->lng);
        $workerArr = $event->worker->toArray();
        if (!empty($workerArr['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::WORKER_FORMS,
                "notificationData" => [
                    'worker' =>  $workerArr
                ]
            ]));
        }
        if (!empty($event->worker->email)) {
            try {
                Mail::send('/Mails/WorkerForms', $workerArr, function ($messages) use ($workerArr, $admin) {
                    $messages->to($workerArr['email']);
                    $messages->bcc(config('services.mail.default'));
                    if($admin){
                        $messages->bcc($admin->email);  
                    }
                    ($workerArr['lng'] == 'heb') ?
                        $sub = $workerArr['id'] . "# " . __('mail.forms.worker_forms') :
                        $sub = __('mail.forms.worker_forms') . " #" . $workerArr['id'];
                    $messages->subject($sub);
                });
            } catch (\Exception $e) {
                report($e);
            }
        }else{
            if($admin){
                Mail::send('/Mails/WorkerForms', $workerArr, function ($messages) use ($workerArr, $admin) {
                    $messages->to($admin->email);  
                    $messages->bcc(config('services.mail.default'));
                    ($workerArr['lng'] == 'heb') ?
                        $sub = $workerArr['id'] . "# " . __('mail.forms.worker_forms') :
                        $sub = __('mail.forms.worker_forms') . " #" . $workerArr['id'];
                    $messages->subject($sub);
                });
            }
        }
    }
}
