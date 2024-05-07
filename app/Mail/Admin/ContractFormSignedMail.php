<?php

namespace App\Mail\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ContractFormSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $admin, $worker, $form;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\User  $worker
     * @return void
     */
    public function __construct($admin, User $worker, $form)
    {
        $this->admin = $admin;
        $this->worker = $worker;
        $this->form = $form;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $pdfPath = Storage::disk('public')->path('signed-docs/' . $this->form->pdf_name);

        return $this->view('Mails.admin.contract-form-signed')
            ->with([
                'admin' => $this->admin,
                'worker' => $this->worker,
                'lng' => 'en'
            ])
            ->attach($pdfPath, [
                'as' => __('mail.contract-form.form_name'),
                'mime' => 'application/pdf'
            ])
            ->subject(__('mail.worker.contract-signed.subject'));
    }
}
