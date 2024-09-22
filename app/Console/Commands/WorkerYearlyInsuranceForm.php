<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\User;

class WorkerYearlyInsuranceForm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:notify-yearly-insurance-form';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify worker about yearly insurance form';

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
        $today = Carbon::today();

        if ($today->month != 1 || $today->day != 1) {
            return 0;
        }

        $workers = User::query()
            ->where('country', '!=', 'Israel')
            ->where('company_type', '=', 'my-company')
            ->get();

        foreach ($workers as $key => $worker) {
            App::setLocale($worker->lng);
            $workerArr = $worker->toArray();

            if (!empty($workerArr['phone'])) {
                // event(new WhatsappNotificationEvent([
                //     "type" => WhatsappMessageTemplateEnum::WORKER_SAFE_GEAR,
                //     "notificationData" => $workerArr
                // ]));
            }

            // Mail::send('Mails.worker.insurance-form', $workerArr, function ($messages) use ($workerArr) {
            //     $messages->to($workerArr['email']);
            //     $messages->subject(__('mail.worker.insurance-form.subject'));
            // });
        }

        return 0;
    }
}
