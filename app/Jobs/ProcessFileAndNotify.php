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
    }
}
