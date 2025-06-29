<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Form;
use App\Models\InsuranceCompany;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;


class TerminateTheWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terminate:worker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate the worker if their leave date is today';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get today's date
        $today = Carbon::today()->toDateString();

        // Find users whose last_work_date is today and update their status
        $workers = User::with('forms')->where('status', '!=', 0)->whereDate('last_work_date', $today)->get();
        $insuranceCompany = InsuranceCompany::first();


        if ($workers->isEmpty()) {
            $this->info('No workers to terminate today.');
        } else {
            foreach ($workers as $worker) {
                $worker->update(['status' => 0]);
                $pdfFile = null;
                $insuranceForm = $worker->forms()->where('type', 'insurance')->first();
                if ($insuranceForm) {
                    $file_name = $insuranceForm->pdf_name;
                    $pdfFile = storage_path("app/public/signed-docs/{$file_name}");
                }

                if ($insuranceCompany && $insuranceCompany->email && $pdfFile) {
                    App::setLocale('heb');
                    $template = ($worker->country == 'Israel') ? '/stopInsuaranceFormIsrael' : '/stopInsuaranceFormNonIsrael';
                    $subjectKey = ($worker->country == 'Israel') ? 'mail.stop_insuarance_form_israel.subject' : 'mail.stop_insuarance_form_non_israel.subject';
                    
                    // Send email
                    Mail::send($template, ['worker' => $worker], function ($message) use ($worker, $insuranceCompany, $pdfFile, $subjectKey) {
                        $message->to($insuranceCompany->email)
                            ->bcc(config('services.mail.default'))
                            ->subject(__($subjectKey, ['worker_name' => ($worker['firstname'] ?? '') . ' ' . ($worker['lastname'] ?? '')]));
                        if(is_file($pdfFile)) {
                            $message->attach($pdfFile);
                        }
                    });
                }

                $this->info("Worker ID: {$worker->id}, Name: {$worker->firstname} {$worker->lastname} has been terminated.");
            }
        }

        return 0;
    }
}
