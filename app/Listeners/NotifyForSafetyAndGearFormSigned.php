<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\SafetyAndGearFormSigned;
use App\Events\WhatsappNotificationEvent;
use App\Mail\Admin\SafetyAndGearFormSignedMail as AdminSafetyAndGearFormSignedMail;
use App\Mail\Worker\SafetyAndGearFormSignedMail;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class NotifyForSafetyAndGearFormSigned implements ShouldQueue
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
     * @param  \App\Events\SafetyAndGearFormSigned  $event
     * @return void
     */
    public function handle(SafetyAndGearFormSigned $event)
    {
        $worker = $event->worker;
        // no whatsapp notification to admin in group
        Notification::create([
            'user_id' => $event->worker->id,
            'user_type' => get_class($event->worker),
            'type' => NotificationTypeEnum::SAFETY_GEAR_SIGNED,
            'status' => 'signed'
        ]);

        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        if ($event->worker->company_type == 'manpower') {
            App::setLocale('heb');

            // **Retrieve all forms of the worker**
            $workerForms = $event->worker->forms()->get();
            $attachments = [];
            $workerName = trim(trim($event->worker->firstname ?? '') . '-' . trim($event->worker->lastname ?? ''));
            $admin = Admin::where('role', 'hr')->first();

            foreach ($workerForms as $workerForm) {
                $formType = $workerForm->type; // e.g., "form101"
                $filePath = storage_path("app/public/signed-docs/{$workerForm->pdf_name}");

                if (file_exists($filePath)) {
                    $workerIdentifier = $event->worker->id_number ?: $event->worker->passport;
                    $fileName = "{$formType}-{$workerName}-{$workerIdentifier}.pdf";
                    $fileName = str_replace(' ', '-', $fileName);

                    $attachments[$filePath] = $fileName;
                }
            }
            // Send email with all form attachments
            Mail::send('/sendAllFormsToAdmin', ["worker" => $event->worker], function ($message) use ($worker, $attachments, $admin) {
                $message->to(config("services.mail.default"));
                if ($admin) {
                    $message->bcc($admin->email);
                }
                $message->subject(__('mail.all_forms.subject'));

                // Attach all available forms
                foreach ($attachments as $filePath => $fileName) {
                    $message->attach($filePath, ['as' => $fileName]);
                }
            });
        }

        // App::setLocale('en');
        // foreach ($admins as $key => $admin) {
        //     Mail::to($admin->email)->send(new AdminSafetyAndGearFormSignedMail($admin, $event->worker, $event->form));
        // }

        // App::setLocale($event->worker->lng);

        // Mail::to($event->worker->email)
        // ->bcc(config('services.mail.default'))
        // ->send(new SafetyAndGearFormSignedMail($event->worker, $event->form));
    }
}
