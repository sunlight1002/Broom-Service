<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App;
use Illuminate\Broadcasting\InteractsWithSockets;


class SendUninterestedClientEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $client;
    protected $emailData;

    /**
     * Create a new job instance.
     *
     * @param $client
     * @param $emailData
     */
    public function __construct($client, $emailData)
    {
        $this->client = $client;
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('Email sending ');

        try {
            $fileName = $this->client['lng'] === 'heb' ? 'BroomServiceHebrew.pdf' : 'BroomServiceEnglish.pdf';

            $pdfPath = Storage::path($fileName);
    
            App::setLocale($this->client['lng']);
    
            // Mail::send('Mails.FollowUpOurConversation', ['client' => $this->emailData['client']], function ($message) use ($pdfPath, $fileName) {
            //     $message->to($this->client['email'])
            //             ->subject(__('mail.follow_up_conversation.header'))
            //             ->attach($pdfPath, [
            //                 'as' => $fileName,
            //                 'mime' => 'application/pdf',
            //             ]);
            // });
    
            \Log::info('Email sent to client: ' . $this->client['email']);
    
        } catch (\Exception $e) {
            \Log::error('Failed to send email to client: ' . $this->client['email'] . ' - Error: ' . $e);
        }
    }
    
}
