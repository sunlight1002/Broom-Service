<?php

namespace App\Console\Commands;

use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class WorkerNotifyNextDayJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:notify-next-day-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify worker about next day job';

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
        $tomorrow = Carbon::tomorrow()->toDateString();

        $jobs = Job::query()
            ->with(['worker', 'client', 'jobservice', 'propertyAddress'])
            ->whereNotNull('worker_id')
            ->whereHas('worker')
            ->where('is_worker_reminded', false)
            ->whereDate('start_date', $tomorrow)
            ->get();

        foreach ($jobs as $key => $job) {
            $worker = $job->worker;

            App::setLocale($worker['lng']);

            $emailData = array(
                'email' => $worker['email'],
                'job'  => $job->toArray(),
                'content'  => __('mail.worker_tomorrow_job.message') . " " . __('mail.worker_new_job.please_check'),
            );

            Mail::send('/Mails/WorkerRemindJobMail', $emailData, function ($messages) use ($emailData) {
                $messages->to($emailData['email']);
                $sub = __('mail.worker_tomorrow_job.subject');
                $messages->subject($sub);
            });

            $job->update([
                'is_worker_reminded' => true
            ]);
        }

        return 0;
    }
}
