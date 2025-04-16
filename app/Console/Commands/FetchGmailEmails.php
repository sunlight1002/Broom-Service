<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client as Google_Client;
use Google\Service\Gmail as Google_Service_Gmail;
use Google\Service\Gmail\Message as Google_Service_Gmail_Message;
use App\Models\UserSetting;
use App\Enums\SettingKeyEnum;
use App\Models\Client;
use App\Models\User;
use App\Models\ScheduleChange;
use App\Models\Setting;
use Aws\Api\Service;
use Google\Service\CloudSearch\UserId;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;


class FetchGmailEmails extends Command
{
    protected $signature = 'gmail:fetch';
    protected $description = 'Fetch emails from Gmail for all users with valid tokens';

    public function __construct()
    {
        parent::__construct();
    }

    protected $urgent_contact = [
        "en" => "🔔 Client has requested an urgent callback regarding: :message\n\n📞 Phone: :client_phone\n:comment_link\n📄 :client_link",
        "heb" => "🔔 לקוח בשם ביקש שיחזרו אליו בדחיפות בנושא: :message\n\n📞 טלפון: :client_phone\n:comment_link\n📄 :client_link"
    ];


    public function handle()
    {
        // Fetch all user access tokens
        $userSettings = Setting::where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)->get();

        Log::info("UserSettings", [$userSettings]);

        if ($userSettings->isEmpty()) {
            $this->info('No users found with a valid Google Access Token.');
            return;
        }

        foreach ($userSettings as $setting) {
            $userId = $setting->id;
            Log::info('Processing User ID:', [$userId]);


            $googleAccessToken = $setting->value;
            $refreshTokenSetting = Setting::where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)->first();

            $googleRefreshToken = $refreshTokenSetting ? $refreshTokenSetting->value : null;

            $client = new Google_Client();
            $client->setApplicationName('Gmail API Laravel');
            $client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
            $client->setAccessToken($googleAccessToken);


            if ($client->isAccessTokenExpired()) {
                if ($googleRefreshToken) {
                    try {
                        $newToken = $client->fetchAccessTokenWithRefreshToken($googleRefreshToken);

                        if (isset($newToken['access_token'])) {
                            // Update the access token in the database
                            Setting::updateOrCreate(
                                ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                                ['value' => $newToken['access_token']]
                            );

                            Log::info("Updated Access Token for User ID: {$userId}");
                            $client->setAccessToken($newToken['access_token']);
                        }

                        if (isset($newToken['refresh_token'])) {
                            // Update the refresh token if it is returned
                            Setting::updateOrCreate(
                                ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                                ['value' => $newToken['refresh_token']]
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error("Error refreshing token for User ID {$userId}: " . $e->getMessage());
                        $this->error("Failed to refresh token for User ID {$userId}. Please reconnect Google account.");
                        continue;
                    }
                } else {
                    $this->warn("No refresh token found for User ID: {$userId}. Please reconnect the Google account.");
                    continue;
                }
            }

            $service = new Google_Service_Gmail($client);

            $user = 'me';

            try {
                $fiveMinutesAgo = now()->subMinutes(15)->timestamp;
                $query = "after:{$fiveMinutesAgo}";
                \Log::info($query);
                
                $messages = $service->users_messages->listUsersMessages($user, ['q' => $query]);
                
                if (count($messages->getMessages()) == 0) {
                    $this->info("No emails found for user ID: {$userId}.");
                } else {
                    foreach ($messages->getMessages() as $message) {
                        $msg = $service->users_messages->get($user, $message->getId(), ['format' => 'full']);

                        $payload = $msg->getPayload();
                        $headers = $payload->getHeaders();

                        $fromEmail = $toEmail = $subject = '';
                        $matched = false;

                        foreach ($headers as $header) {
                            if ($header->getName() == 'From') {
                                $fromEmail = $header->getValue();
                                preg_match('/<([^>]+)>/', $fromEmail, $matches);
                                $email = $matches[1] ?? null;
                        
                                if ($email == "TELESERVICE@beepertalk.co.il") {
                                    $matched = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($matched) {
                            foreach ($headers as $header) {
                                if ($header->getName() == 'To') {
                                    $toEmail = $header->getValue();
                                }
                                if ($header->getName() == 'Subject') {
                                    $subject = $header->getValue();
                                }
                            }

                                $body = '';
                            if ($payload->getBody()->getSize() > 0) {
                                $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
                            } else {
                                $parts = $payload->getParts();
                                foreach ($parts as $part) {
                                    \Log::info([$part]);
                                    \Log::info("mimeType: " . $part['mimeType']);

                                    if ($part['mimeType'] === 'text/plain' || $part['mimeType'] === 'text/html' || in_array($part['mimeType'], ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])) {
                                        $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $part['body']['data']));
                                        break;
                                    }
                                }
                            }
                            preg_match('/\b\d{10}\b/', $body, $matches);
                            $phoneNumber = $matches[0] ?? null;

                            $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

                            // Check if the phone number starts with '0'
                            if (strpos($phoneNumber, '0') === 0) {
                                // Remove the leading '0' and prepend '972'
                                $phoneNumber = '972' . substr($phoneNumber, 1);
                            } elseif (strpos($phoneNumber, '972') === 0) {
                                // If the phone already starts with '972', leave it as is
                                // Ensure no leading '+'
                                $phoneNumber = ltrim($phoneNumber, '+');
                            } elseif (strpos($phoneNumber, '+') === 0) {
                                // If the phone starts with '+', remove the '+'
                                $phoneNumber = substr($phoneNumber, 1);
                            } else {
                                // If no country code is present, prepend '972'
                                $phoneNumber = '972' . $phoneNumber;
                            }


                            $client = null;
                            if ($phoneNumber) {
                                $client = Client::where('phone', $phoneNumber)->first();
                                $user = User::where('phone', $phoneNumber)->first();
                            }

                            if($client && $user){
                                $this->warn("client and user found for phone number: {$phoneNumber}");
                                return;
                            }

                            if (!$client) {
                                $this->warn("No client found for phone number: {$phoneNumber}");
                            
                                $client = Client::create([
                                    'email'          => null,
                                    'payment_method' => 'cc',
                                    'password'       => Hash::make($phoneNumber),
                                    'passcode'       => $phoneNumber,
                                    'status'         => 0,
                                    'lng'            => "heb",
                                    'phone'          => $phoneNumber,
                                ]);
                            } else {
                                $this->info("Matched Client: {$client->firstname}, Phone: {$client->phone}");
                            }
                            
                            // Create the schedule change
                            $scheduleChange = ScheduleChange::create([
                                'user_type'      => Client::class,
                                'reason'         => 'urgent hiring',
                                'comments'       => $body,
                                'user_id'        => $client->id,
                                'status'         => 'pending',
                                'team_response'  => null,
                            ]);
                            
                            // Prepare message replacements
                            $scheduleLink  = generateShortUrl(url('admin/schedule-requests?id=' . $scheduleChange->id), 'admin');
                            $clientLink    = generateShortUrl(url("admin/clients/view/" . $client->id), 'admin');
                            $cleanedText = preg_replace("/(\r?\n){2,}/", "\n", $body);
                            $trimmedBody   = '*' . trim($cleanedText) . '*';

                            
                            // Replace placeholders in the message
                            $personalizedMessage = str_replace(
                                [':message', ':client_phone', ':comment_link', ':client_link'],
                                [$trimmedBody, $client->phone, $scheduleLink, $clientLink],
                                $this->urgent_contact["heb"]
                            );
                            
                            // Send the message to the team
                            sendTeamWhatsappMessage(
                                config('services.whatsapp_groups.urgent'),
                                ['name' => '', 'message' => $personalizedMessage]
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error fetching Gmail emails for user ID {$userId}: " . $e->getMessage());
                $this->error("Error for User ID {$userId}: " . $e->getMessage());
            }
        }
    }
}
