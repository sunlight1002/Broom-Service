<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Twilio\Rest\Client;

class TwilioCall extends Command
{
    // The name and signature of the console command.
    protected $signature = 'call:twilio';

    // The console command description.
    protected $description = 'Make a call using Twilio and specify the text to be spoken';

    // Execute the console command.
    public function handle()
    {
        // Retrieve the phone number and text from the command arguments
        // $phone = $this->argument('phone');
        $text = "hello i m";

        // Twilio credentials from .env file
        $twilioAccountSid = config('services.twilio.twilio_id');
        $twilioAuthToken = config('services.twilio.twilio_token');
        $twilioPhoneNumber = config('services.twilio.twilio_number');

        // Initialize the Twilio client
        $twilio = new Client($twilioAccountSid, $twilioAuthToken);

        // Generate TwiML response
        // $twiml = "<Response><Say>{$text}</Say></Response>";

        // Use a temporary endpoint to serve TwiML
        $twimlUrl = 'https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml';

        try {
            // // Make the call
            // $call = $twilio->calls->create(
            //     $phone, // Destination phone number
            //     $twilioPhoneNumber, // Twilio phone number
            //     [
            //         'url' => $twimlUrl // TwiML URL
            //     ]
            // );

            $client = "1";

            $message = $twilio->messages->create(
                "whatsapp:+918000318833", // To (Phone number in international format)
                [
                    "from" => "whatsapp:+972526954864", // From (Twilio WhatsApp number)
                    "body" => $text, // Message body
                    // "contentSid" => "HX3732b37820ac96e08bfbd8bacf752541", // Your approved template's Content SID
                    // "contentVariables" => json_encode([
                    //     "6" => "admin/leads/view/" . $client,
                    // ])
                    // "statusCallback" => "https://612a-2405-201-2022-10c3-1484-7d36-5a49-eef1.ngrok-free.app/twilio/webhook"
                ]
            );
            
            
            

            $this->info('Message sent successfully with SID: ' . $message->sid);

            // Output call SID for reference
            // $this->info('Call initiated successfully with SID: ' . $call->sid);
        } catch (\Exception $e) {
            // Handle exceptions
            $this->error('Error making call: ' . $e->getMessage());
        }
    }
}