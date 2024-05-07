<?php

namespace App\Mail\Client;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class JobReviewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $job;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\User  $worker
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('Mails.client.job-review')
            ->with([
                'job' => $this->job,
            ])
            ->subject(__('mail.client.review-request.subject'));
    }
}
