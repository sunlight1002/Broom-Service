<?php

namespace App\Listeners;

use App\Enums\NotificationTypeEnum;
use App\Enums\WorkerFormTypeEnum;
use App\Events\InsuranceFormSigned;
use App\Mail\Admin\InsuranceFormSignedMail as AdminInsuranceFormSignedMail;
use App\Mail\Worker\InsuranceFormSignedMail;
use App\Models\Admin;
use App\Models\InsuranceCompany;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class NotifyForInsuranceFormSigned implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\InsuranceFormSigned  $event
     * @return void
     */
    public function handle(InsuranceFormSigned $event)
    {
        $worker = $event->worker;
        $form = $event->form;
        $insuranceCompany = InsuranceCompany::first();

        Notification::create([
            'user_id' => $worker->id,
            'user_type' => get_class($worker),
            'type' => NotificationTypeEnum::INSURANCE_SIGNED,
            'status' => 'signed'
        ]);

        $admins = Admin::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get(['name', 'email', 'id', 'phone']);

        $form101 = $worker->forms()
            ->where('type', WorkerFormTypeEnum::FORM101)
            ->first();
        $insuaranceForm = $worker->forms()->where('type', 'insurance')->first();

        $file_name = $insuaranceForm ? $insuaranceForm->pdf_name : null;

        $dateOfBeginningWork = $form101 ? data_get($form101->data, 'DateOfBeginningWork') : null;
        $workerName = trim(trim($worker->firstname ?? '') . '-' . trim($worker->lastname ?? ''));
        App::setLocale('heb');

        if ($insuranceCompany && $insuranceCompany->email && $file_name) {
            $pdfPath = storage_path("app/public/signed-docs/{$file_name}");

            $workerPassport = $worker->passport_card ?? null;
            $workerVisa = $worker->visa ?? null;

            $workerPassportDocName = str_replace(' ', '-', "Passport-{$workerName}");
            $workerVisaDocName = str_replace(' ', '-', "Visa-{$workerName}");

            // Choose template based on worker's country
            $template = ($worker->country == 'Israel') ? '/insuaranceCompanyIsrael' : '/insuaranceCompany';

            Mail::send(
                $template,
                ['worker' => $worker, 'dateOfBeginningWork' => $dateOfBeginningWork],
                function ($message) use ($worker, $insuranceCompany, $file_name, $workerPassport, $workerPassportDocName, $workerVisa, $workerVisaDocName, $pdfPath) {
                    // Choose subject based on worker's country
                    $subjectKey = ($worker->country == 'Israel') ? 'mail.insuarance_company_israel.subject' : 'mail.insuarance_company.subject';
                    
                    $message->to($insuranceCompany->email)
                        ->subject(__($subjectKey, [
                            'worker_name' => ($worker['firstname'] ?? '') . ' ' . ($worker['lastname'] ?? '')
                        ]));
                    $message->bcc(config('services.mail.default'));
                    if (is_file($pdfPath)) {
                        $message->attach($pdfPath);
                    }

                    if ($workerPassport) {
                        $message->attach(storage_path("app/public/uploads/documents/{$workerPassport}"), ['as' => $workerPassportDocName]);
                    }

                    if ($workerVisa) {
                        $message->attach(storage_path("app/public/uploads/documents/{$workerVisa}"), ['as' => $workerVisaDocName]);
                    }
                }
            );
        }

        // Send all worker forms to HR
        $workerForms = $worker->forms()->get();
        $attachments = [];
        $admin = Admin::where('role', 'hr')->first();

        foreach ($workerForms as $workerForm) {
            $formType = $workerForm->type;
            $filePath = storage_path("app/public/signed-docs/{$workerForm->pdf_name}");

            if (file_exists($filePath)) {
                $workerIdentifier = $worker->id_number ?: $worker->passport;
                $fileName = str_replace(' ', '-', "{$formType}-{$workerName}-{$workerIdentifier}.pdf");
                $attachments[$filePath] = $fileName;
            }
        }

        Mail::send('/sendAllFormsToAdmin', ["worker" => $worker], function ($message) use ($worker, $attachments, $admin) {
            $message->to(config('services.mail.default'));
            if ($admin) {
                $message->bcc($admin->email);
            }
            $message->subject(__('mail.all_forms.subject'));

            foreach ($attachments as $filePath => $fileName) {
                $message->attach($filePath, ['as' => $fileName]);
            }
        });

        // App::setLocale($worker->lng);

        // Mail::to($worker->email)->send(new InsuranceFormSignedMail($worker, $form));
    }
}
