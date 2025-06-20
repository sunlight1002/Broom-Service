<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Holiday;
use App\Models\HolidayNotification;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use Carbon\Carbon;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;

class NotifyManagementAboutJewishHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:jewish-holidays-management';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify management group 2 weeks before Jewish holidays for schedule and message management';

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
        $twoWeeksFromNow = $today->copy()->addWeeks(2);

        // Get holidays that start within the next 2 weeks
        $upcomingHolidays = Holiday::where('start_date', '>=', $today)
            ->where('start_date', '<=', $twoWeeksFromNow)
            ->orderBy('start_date', 'asc')
            ->get();

        foreach ($upcomingHolidays as $holiday) {
            // Check if we've already sent a notification for this holiday
            $notificationSent = HolidayNotification::where('holiday_id', $holiday->id)
                ->where('notification_type', 'two_weeks_before')
                ->exists();

            if (!$notificationSent) {
                $this->sendHolidayNotification($holiday);
                
                // Mark that we've sent the notification
                HolidayNotification::create([
                    'holiday_id' => $holiday->id,
                    'notification_type' => 'two_weeks_before',
                    'sent_at' => now(),
                ]);

                $this->info("Sent notification for holiday: {$holiday->holiday_name} starting on {$holiday->start_date}");
            } else {
                $this->info("Notification already sent for holiday: {$holiday->holiday_name}");
            }
        }

        return 0;
    }

    /**
     * Send holiday notification to management group
     *
     * @param Holiday $holiday
     * @return void
     */
    private function sendHolidayNotification($holiday)
    {
        $startDate = Carbon::parse($holiday->start_date);
        $endDate = Carbon::parse($holiday->end_date);
        
        // Calculate days until holiday
        $daysUntilHoliday = Carbon::today()->diffInDays($startDate);
        
        // Determine holiday duration
        $duration = $startDate->diffInDays($endDate) + 1;
        $durationText = $duration == 1 ? 'יום אחד' : "{$duration} ימים";

        // Prepare notification data
        $notificationData = [
            'holiday' => [
                'name' => $holiday->holiday_name,
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'duration' => $durationText,
                'days_until' => $daysUntilHoliday,
                'is_full_day' => $holiday->full_day,
                'is_half_day' => $holiday->half_day,
                'first_half' => $holiday->first_half,
                'second_half' => $holiday->second_half,
            ]
        ];

        // Create notification in the main Notification table for admin panel visibility
        Notification::create([
            'user_id' => 1, // Using admin user ID 1 as default
            'user_type' => 'App\Models\Admin',
            'type' => NotificationTypeEnum::JEWISH_HOLIDAY_NOTIFICATION,
            'status' => 'holiday_notification',
            'data' => [
                'holiday_name' => $holiday->holiday_name,
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'duration' => $durationText,
                'days_until' => $daysUntilHoliday,
                'holiday_id' => $holiday->id,
            ]
        ]);

        // Send WhatsApp notification to management group
        event(new WhatsappNotificationEvent([
            "type" => WhatsappMessageTemplateEnum::JEWISH_HOLIDAY_NOTIFICATION_TO_MANAGEMENT,
            "notificationData" => $notificationData
        ]));
    }
} 