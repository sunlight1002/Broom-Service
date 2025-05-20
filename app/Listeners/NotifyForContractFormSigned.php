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

        $worker = $event->worker;
        $form = $event->form;
        // no whatsapp notification to admin in group
        Notification::create([
            'user_id' => $worker->id,
            'user_type' => get_class($worker),
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

        if ($worker->company_type == 'my-company' && $worker->country == 'Israel') {
            App::setLocale('heb');

            // **Retrieve all forms of the worker**
            $workerForms = $worker->forms()->get();
            $attachments = [];
            $workerName = trim(($worker->firstname ?? '') . '-' . ($worker->lastname ?? ''));
            $admin = Admin::where('role', 'hr')->first();

            foreach ($workerForms as $workerForm) {
                $formType = $workerForm->type; // e.g., "form101"
                $filePath = storage_path("app/public/signed-docs/{$workerForm->pdf_name}");

                if (file_exists($filePath)) {
                    $workerIdentifier = $worker->id_number ?: $worker->passport;
                    $fileName = "{$formType}-{$workerName}-{$workerIdentifier}.pdf";
                    $fileName = str_replace(' ', '-', $fileName);

                    $attachments[$filePath] = $fileName;
                }
            }
            // Send email with all form attachments
            Mail::send('/sendAllFormsToAdmin', ["worker" => $worker], function ($message) use ($worker, $attachments, $admin) {
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

        App::setLocale($worker->lng);

        Mail::to($worker->email)
            ->bcc(config('services.mail.default'))
            ->send(new ContractFormSignedMail($worker, $form));
    }
}
