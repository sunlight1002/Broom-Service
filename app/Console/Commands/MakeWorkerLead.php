<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Client;
use App\Models\WorkerLeads;
use Illuminate\Support\Facades\Log;

class MakeWorkerLead extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:worker-lead';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make worker lead with phone number validation and language detection';

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
        $en = [
            "91004238947",
            "094823o04823"
            
        ];

        $rus = [
            "023423423",
            "032423234234"
        ];

        $phones = array_merge($en, $rus);

        foreach ($phones as $phone) {
            $lng = in_array($phone, $rus) ? 'ru' : 'en';

            $formattedPhone = $this->fixedPhoneNumber($phone);

            $user = User::where('phone', $formattedPhone)->first();
            $client = Client::where('phone',$formattedPhone)->first();
            $workerLead = WorkerLeads::where('phone', $formattedPhone)->first();

            if (!$user && !$client && !$workerLead) {

                $workerLead = WorkerLeads::create([
                    'phone' => $formattedPhone,
                    'lng' => $lng,
                    'status' => "pending",
                ]);

                $this->sendMessage($formattedPhone, $lng);

                Log::info("Worker Lead created for phone: $formattedPhone $lng");
            } else {
                Log::info("in User table: $formattedPhone");
            }
        }

        return 0;
    }

    /**
     * Fixed phone number formatting function.
     *
     * @param string $phone
     * @return string
     */
    public function fixedPhoneNumber($phone){
        // $phone = $client->phone;

        // 1. Remove all special characters from the phone number
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // 2. If there's any string or invalid characters in the phone, extract the digits
        if (preg_match('/\d+/', $phone, $matches)) {
            $phone = $matches[0]; // Extract the digits

            // Reapply rules on extracted phone number
            // If the phone number starts with 0, add 972 and remove the first 0
            if (strpos($phone, '0') === 0) {
                $phone = '972' . substr($phone, 1);
            }

            // If the phone number starts with +, remove the +
            if (strpos($phone, '+') === 0) {
                $phone = substr($phone, 1);
            }
        }

        $phoneLength = strlen($phone);
        if (($phoneLength === 9 || $phoneLength === 10) && strpos($phone, '972') !== 0) {
            $phone = '972' . $phone;
        }

        return $phone;
    }

    /**
     * Send SMS to the given phone number.
     *
     * @param string $phone
     * @param string $lng
     * @return void
     */
    public function sendMessage($phone, $lng)
    {

        $message = [
            "en" => "Hi! This is Alex from Job4Service.com.
We currently have open cleaning positions in the Tel Aviv area with steady hours. We offer flexible or part-time schedules and pay the highest salary in the cleaning industry—legally, with insurance and a payslip.

We’ve been in business for over 10 years, working with VIP and government clients.
Important: We only hire people with a suitable work visa/ID (B1 visa, refugee “blue” visa, or Israeli ID). If you don’t have this, we can’t help.

Also, please note we don’t offer evening-only or Friday/Saturday-only work, but part-time is possible.

Interested? Send me a message and we’ll talk!",
            "ru" => "Привет! Это Алекс из Job4Service.com.

У нас есть открытые вакансии уборщиков в районе Тель-Авива с стабильными часами. Мы предлагаем гибкий или частичный график и самую высокую зарплату в сфере уборки — всё официально, со страховкой и расчётной ведомостью (payslip).

Мы работаем более 10 лет и обслуживаем VIP-клиентов и государственные учреждения.
Важно: нанимаем только при наличии действующей рабочей визы (B1), «синей» визы беженца или израильского удостоверения личности. Если таких документов нет, мы, к сожалению, помочь не сможем.

Обратите внимание, что у нас нет вакансий только на вечер или только на пятницу/субботу, но частичная занятость возможна.

Заинтересованы? Напишите мне, и мы всё обсудим!"
        ];

        \Log::info($message[$lng]);

        // sendWorkerWhatsappMessage($phone, ['name' => '', 'message' => $message[$lng]]);

    }
}
