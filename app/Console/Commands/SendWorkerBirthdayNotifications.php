<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;

class SendWorkerBirthdayNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workers:send-birthday-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday notifications to workers whose birthday is today';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today();
        
        // Find workers whose birthday is today
        $workersWithBirthday = User::whereNotNull('birth_date')
            ->whereDate('birth_date', $today->format('Y-m-d'))
            ->get();

        if ($workersWithBirthday->isEmpty()) {
            $this->info('No workers have birthdays today.');
            return 0;
        }

        $this->info("Found {$workersWithBirthday->count()} worker(s) with birthday today.");

        foreach ($workersWithBirthday as $worker) {
            try {
                // Create notification record with correct data format
                $notification = Notification::create([
                    'user_id' => $worker->id,
                    'type' => NotificationTypeEnum::WORKER_BIRTHDAY,
                    'data' => [
                        'message' => "Happy Birthday {$worker->firstname}! We hope you have a wonderful day filled with joy and happiness. Thank you for being part of our team!"
                    ],
                    'seen' => 0,
                ]);

                // Send WhatsApp message using the existing helper function
                $message = "🎉 Happy Birthday {$worker->firstname}! 🎂\n\nWe hope you have a wonderful day filled with joy and happiness. Thank you for being part of our amazing team!\n\nBest wishes,\nThe Broom Service Team";
                
                sendWorkerWhatsappMessage($worker->phone, [
                    'message' => $message
                ]);

                $this->info("Birthday notification sent to {$worker->firstname} {$worker->lastname} ({$worker->phone})");
                
            } catch (\Exception $e) {
                $this->error("Failed to send birthday notification to {$worker->firstname} {$worker->lastname}: " . $e->getMessage());
            }
        }

        $this->info('Birthday notification process completed.');
        return 0;
    }
} 