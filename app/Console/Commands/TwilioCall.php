<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Twilio\Rest\Client;

class TwilioCall extends Command
{
    // The name and signature of the console command.
    protected $signature = 'call:twilio {phone}';

    // The console command description.
    protected $description = 'Make a call using Twilio and specify the text to be spoken';

    // Execute the console command.
    public function handle()
    {
        // Retrieve the phone number and text from the command arguments
        $phone = $this->argument('phone');
        $text = "hello i m";

        // Twilio credentials from .env file
        $twilioAccountSid = config('services.twilio.twilio_id');
        $twilioAuthToken = config('services.twilio.twilio_token');
        $twilioPhoneNumber = config('services.twilio.twilio_number');

        // Initialize the Twilio client
        $twilio = new Client($twilioAccountSid, $twilioAuthToken);

        // Use a temporary endpoint to serve TwiML
        $twimlUrl = config("services.twilio.webhook") . 'api/twiml';

        try {
            // Make the call
            $call = $twilio->calls->create(
                $phone, // Destination phone number
                $twilioPhoneNumber, // Twilio phone number
                [
                    'url' => $twimlUrl // TwiML URL
                ]
            );

            // Output call SID for reference
            $this->info('Call initiated successfully with SID: ' . $call->sid);
        } catch (\Exception $e) {
            // Handle exceptions
            $this->error('Error making call: ' . $e->getMessage());
        }
    }
}