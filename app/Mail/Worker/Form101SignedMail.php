<?php

namespace App\Mail\Worker;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class Form101SignedMail extends Mailable
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

        return $this->view('Mails.worker.form101-signed')
            ->with([
                'worker' => $this->worker,
            ])
            ->attach($pdfPath, [
                'as' => __('mail.form101.form_name'),
                'mime' => 'application/pdf'
            ])
            ->subject(__('mail.worker.form101-signed.subject'));
    }
}
