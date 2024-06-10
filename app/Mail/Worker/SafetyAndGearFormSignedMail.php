<?php

namespace App\Mail\Worker;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SafetyAndGearFormSignedMail extends Mailable
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

        return $this->view('Mails.worker.safety-and-gear-form-signed')
            ->with([
                'worker' => $this->worker,
            ])
            ->attach($pdfPath, [
                'as' => __('mail.safety-and-gear-form.form_name') . '.pdf',
                'mime' => 'application/pdf'
            ])
            ->subject(__('mail.worker.safety-and-gear-signed.subject'));
    }
}
