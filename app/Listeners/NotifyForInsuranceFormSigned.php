<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\InsuranceFormSigned;
use App\Events\WhatsappNotificationEvent;
use App\Mail\Admin\InsuranceFormSignedMail as AdminInsuranceFormSignedMail;
use App\Mail\Worker\InsuranceFormSignedMail;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class NotifyForInsuranceFormSigned implements ShouldQueue
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
     * @param  \App\Events\InsuranceFormSigned  $event
     * @return void
     */
    public function handle(InsuranceFormSigned $event)
    {
        // no whatsapp notification to admin in group
        Notification::create([
            'user_id' => $event->worker->id,
            'user_type' => get_class($event->worker),
            'type' => NotificationTypeEnum::INSURANCE_SIGNED,
            'status' => 'signed'
        ]);

        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        // App::setLocale('en');
        // foreach ($admins as $key => $admin) {
        //     Mail::to($admin->email)->send(new AdminInsuranceFormSignedMail($admin, $event->worker, $event->form));
        // }

        App::setLocale($event->worker->lng);

        Mail::to($event->worker->email)->send(new InsuranceFormSignedMail($event->worker, $event->form));
    }
}
