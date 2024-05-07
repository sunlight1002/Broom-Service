<?php

namespace App\Mail\Worker;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ContractFormSignedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $worker, $form;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\User  $worker
     * @return void
     */
    public function __construct(User $worker, $form)
    {
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

        return $this->view('Mails.worker.contract-form-signed')
            ->with([
                'worker' => $this->worker,
            ])
            ->attach($pdfPath, [
                'as' => __('mail.contract-form.form_name'),
                'mime' => 'application/pdf'
            ])
            ->subject(__('mail.worker.contract-signed.subject'));
    }
}
