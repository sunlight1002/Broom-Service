<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadActivity;
use Carbon\Carbon;
use Twilio\Rest\Client as TwilioClient;

class VoiceCallBotInitiated extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voice:call-bot-initiated';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Voice call bot initiated';

    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioPhoneNumber;
    protected $twilio;
    protected $twimlUrl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioPhoneNumber = config('services.twilio.twilio_number');
        $this->twimlUrl = config("services.twilio.webhook") . 'api/twiml';

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();
        $today = $now->toDateString();
    
        // Optional 5-minute window
        $timeWindowStart = $now->copy()->subMinutes(5)->format('H:i:s');
        $timeWindowEnd = $now->copy()->addMinutes(5)->format('H:i:s');
    
        // Fetch lead activities with 'voice bot' status scheduled for now, where client's voice_bot != 1
        $leadActivities = LeadActivity::where('changes_status', 'voice bot')
            ->whereDate('voice_bot_call_date', $today)
            ->whereTime('voice_bot_call_time', '>=', $timeWindowStart)
            ->whereTime('voice_bot_call_time', '<=', $timeWindowEnd)
            ->whereHas('client', function ($query) {
                $query->where('voice_bot', '!=', 1);
            })
            ->with('client')
            ->get();
    
        foreach ($leadActivities as $activity) {
            $client = $activity->client;
            $phone = $client->phone ?? null;
    
            if (!$phone) {
                $this->warn("Client ID {$activity->client_id} has no phone number.");
                continue;
            }
    
            try {
                $call = $this->twilio->calls->create(
                    $phone,
                    $this->twilioPhoneNumber,
                    ['url' => $this->twimlUrl]
                );
    
                $this->info("Call initiated for client ID {$client->id} - SID: {$call->sid}");
    
                // ✅ Mark client as called to prevent future calls
                $client->update(['voice_bot' => 1]);
    
            } catch (\Exception $e) {
                $this->error("Failed to call client ID {$client->id}: " . $e->getMessage());
            }
        }
    
        return 0;
    }
    
}
