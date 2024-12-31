<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\ContractFormSigned;
use App\Events\WhatsappNotificationEvent;
use App\Mail\Admin\ContractFormSignedMail as AdminContractFormSignedMail;
use App\Mail\Worker\ContractFormSignedMail;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class NotifyForContractFormSigned implements ShouldQueue
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
     * @param  \App\Events\ContractFormSigned  $event
     * @return void
     */
    public function handle(ContractFormSigned $event)
    {
        // no whatsapp notification to admin in group
        Notification::create([
            'user_id' => $event->worker->id,
            'user_type' => get_class($event->worker),
            'type' => NotificationTypeEnum::WORKER_CONTRACT_SIGNED,
            'status' => 'signed'
        ]);

        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        // App::setLocale('en');
        // foreach ($admins as $key => $admin) {
        //     Mail::to($admin->email)->send(new AdminContractFormSignedMail($admin, $event->worker, $event->form));
        // }

        App::setLocale($event->worker->lng);

        Mail::to($event->worker->email)->send(new ContractFormSignedMail($event->worker, $event->form));
    }
}
