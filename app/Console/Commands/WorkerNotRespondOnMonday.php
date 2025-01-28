<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Models\User;
use App\Models\WorkerMetas;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\DB;

class WorkerNotRespondOnMonday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:not_respond_on_monday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::where('status', '1')
        ->where('stop_last_message', 0)
        ->get();

        $message = "שלום צוות,

לא השיב לאישור העדפותיו לסידור העבודה לשבוע הבא.
:worker_name
נא ליצור איתו קשר בהקדם על מנת לפתור את הבעיה.

בברכה,
צוות ברום סרוויס";
        $workersName = [];
        foreach ($users as $user) {
            \Log::info($user->id);
            $workerName = ($user->firstname ?? '') . ' ' . ($user->lastname ?? '');
            $workersName[] = $workerName;

        }
        $personalizedMessage = str_replace(':worker_name', implode("\n", $workersName), $message);
        sendTeamWhatsappMessage(config('services.whatsapp_groups.problem_with_workers'), ['name' => '', 'message' => $personalizedMessage]);
    }
}
