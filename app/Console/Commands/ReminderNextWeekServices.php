<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ReminderNextWeekServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind:next-week-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Every Wednesday, send a notification to all clients informing them of the service they will receive the following week.';

    protected $whapiApiEndpoint;
    protected $whapiApiToken;

    public function __construct()
    {
        // Initialize the parent constructor
        parent::__construct();

        // Set the WHAPI configuration values
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the start and end dates for the following week
        $startOfNextWeek = Carbon::now()->startOfWeek()->addWeek()->format('Y-m-d');
        $endOfNextWeek = Carbon::now()->endOfWeek()->addWeek()->format('Y-m-d');
        \Log::info("Next week's dates: {$startOfNextWeek} to {$endOfNextWeek}");

        // Fetch all Jobs with their related JobService for services happening next week
        $jobs = Job::with(['jobservice', 'propertyAddress'])
                    ->whereBetween('start_date', [$startOfNextWeek, $endOfNextWeek])
            // ->whereHas('jobservice', function($query) use ($startOfNextWeek, $endOfNextWeek) {
            //     $query->whereBetween('created_at', [$startOfNextWeek, $endOfNextWeek]);
            // })
            ->get();

        foreach ($jobs as $job) {
            $jobService = $job->jobservice; 
            $propertyAddress = $job->propertyAddress;
            
            $time = Carbon::parse($jobService->created_at)->format('Y-m-d');

            if ($jobService && $job->client) {
                $client = $job->client;

                if($client->wednesday_notification == 1 || $client->disable_notification == 1){
                    \Log::info("Client {$client->id} has already been notified");
                    continue;
                }

                $response = event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION,
                    "notificationData" => [
                        'client' => $client->toArray(),
                        'property' => $propertyAddress->toArray(),
                    ]
                ]));
            }
        }

        return 0;
    }
}
