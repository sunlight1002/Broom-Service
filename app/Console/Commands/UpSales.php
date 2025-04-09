<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\User;
use App\Models\Job;
use App\Models\Services;
use App\Models\Discount;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;

class UpSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upsales:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Getting worker availability and clients services today after send a message to the client if you add this worker with service then we will get you a discount';

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
        $today = now()->toDateString(); // YYYY-MM-DD
    
        // Step 1: Get jobs scheduled for today (with client_id)
        $clientsWithJobsToday = Job::whereDate('start_date', $today)
            ->whereNotNull('client_id')
            ->get();
    
        foreach ($clientsWithJobsToday as $job) {
            $client = Client::find($job->client_id);
            if (!$client) continue;
    
            $this->info("Checking client: {$client->firstname}");
    
            // Step 2: Get available workers today
            $availableWorkers = User::where('status', 1)
                ->whereHas('availabilities', function ($query) use ($today) {
                    $query->where('date', $today)
                          ->where('status', '1');
                })
                ->get();
    
            $skillIds = collect();
    
            foreach ($availableWorkers as $worker) {
                // Skip worker if already has a job today
                $hasJobToday = Job::where('worker_id', $worker->id)
                    ->whereDate('start_date', $today)
                    ->exists();
    
                if ($hasJobToday) continue;
    
                \Log::info($worker->skill);
                $skills = json_decode($worker->skill, true) ?? [];
    
                $skillIds = $skillIds->merge($skills);
            }
    
            // Step 3: Fetch unique service names
            $uniqueSkillIds = $skillIds->unique()->values();
            $uniqueSkillNames = Services::whereIn('id', $uniqueSkillIds)->pluck('name')->toArray();
    
            if (count($uniqueSkillNames)) {
                $skillList = implode(', ', $uniqueSkillNames);
                $message = "Hey {$client->firstname}, today's available workers have these skills: {$skillList}. Add them to your job today and get a discount!";
                $this->info($message);

                $dtype = Setting::where('key', SettingKeyEnum::DISCOUNT_TYPE)->first();
                $dvalue = Setting::where('key', SettingKeyEnum::DISCOUNT_VALUE)->first();
    
                Discount::create([
                    'client_ids'   => json_encode([$client->id]),
                    'service_ids'  => json_encode($uniqueSkillIds),
                    'type'         => $dtype->value,        
                    'value'   => $dvalue->value,                  
                ]);
    
            }
        }
        return 0;
    }
}
