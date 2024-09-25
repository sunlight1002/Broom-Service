<?php
namespace App\Jobs;

use App\Models\Schedule;
use App\Models\Files;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Events\AdminLeadFilesNotificationJob;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class ProcessFileAndNotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $client;
    protected $schedule;
    protected $type;
    protected $file_nm;
    protected $note;

    public function __construct($userId, $client, $schedule, $type, $file_nm, $note)
    {
        $this->userId = $userId;
        $this->client = $client;
        $this->schedule = $schedule;
        $this->type = $type;
        $this->file_nm = $file_nm;
        $this->note = $note;
    }

    public function handle()
    {
        $files = Files::create([
            'user_id'   => $this->userId,
            'meeting'   => $this->schedule->id,
            'note'      => $this->note,
            'role'      => 'client',
            'type'      => $this->type,
            'file'      => $this->file_nm
        ]);

        event(new AdminLeadFilesNotificationJob($this->schedule, $files));

        $clientNotificationType = $this->client->notification_type;
        if ($clientNotificationType == 'both' || $clientNotificationType == 'whatsapp') {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                "notificationData" => [
                    'client' => $this->client->toArray(),
                ]
            ]));
        }

        if ($clientNotificationType == 'both' || $clientNotificationType == 'email') {
            $leadArray = $this->client->toArray();
            // Uncomment the following to send email notifications
            // App::setLocale($this->client['lng']);
            // Mail::send('Mails.FileSubmissionRequest', ['client' => $leadArray], function ($message) use ($this->client) {
            //     $message->to($this->client->email);
            //     $message->subject(__('mail.file_submission_request.header'));
            // });
        }
    }
}
