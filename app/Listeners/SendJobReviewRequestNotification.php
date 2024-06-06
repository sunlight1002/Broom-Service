<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\JobReviewRequest;
use App\Mail\Client\JobReviewRequestMail;

class SendJobReviewRequestNotification implements ShouldQueue
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
     * @param  \App\Events\JobReviewRequest  $event
     * @return void
     */
    public function handle(JobReviewRequest $event)
    {
        if (!empty($event->job->client->email)) {
            App::setLocale($event->job->client->lng);

            Mail::to($event->job->client->email)->send(new JobReviewRequestMail($event->job));
        }

        $event->job->update([
            'review_request_sent' => true
        ]);
    }
}
