<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client as Google_Client;
use Google\Service\Gmail as Google_Service_Gmail;
use Google\Service\Gmail\Message as Google_Service_Gmail_Message;
use App\Models\UserSetting;
use App\Enums\SettingKeyEnum;
use App\Models\Client;
use App\Models\ScheduleChange;
use App\Models\Setting;
use Aws\Api\Service;
use Google\Service\CloudSearch\UserId;
use Illuminate\Support\Facades\Log;

// class FetchGmailEmails extends Command
// {
//     protected $signature = 'gmail:fetch';
//     protected $description = 'Fetch emails from Gmail for all users with valid tokens';

//     public function __construct()
//     {
//         parent::__construct();
//     }

//     public function handle()
//     {

//         $userSettings = Setting::where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)->get();
//         \Log::info("UserSettings", [$userSettings]);
//         if ($userSettings->isEmpty()) {
//             $this->info('No users found with a valid Google Access Token.');
//             return;
//         }

//         foreach ($userSettings as $setting) {
//             $userId = $setting->id;
//             \Log::info('userId', [$userId]);
//             $googleAccessToken = $setting->value;

//             $client = new Google_Client();
//             $client->setApplicationName('Gmail API Laravel');
//             $client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
//             $client->setAccessToken($googleAccessToken);

//             if ($client->isAccessTokenExpired()) {


//                 $this->warn("Access token expired for user ID: {$userId}. Please reconnect the Google account.");
//                 continue;
//             }

//             $service = new Google_Service_Gmail($client);
//             $user = 'me';

//             try {
//                 $messages = $service->users_messages->listUsersMessages($user, ['maxResults' => 5]);

//                 if (count($messages->getMessages()) == 0) {
//                     $this->info("No emails found for user ID: {$userId}.");
//                 } else {
//                     foreach ($messages->getMessages() as $message) {
//                         $msg = $service->users_messages->get($user, $message->getId());
//                         Log::info("Email for User ID {$userId}: ", ['id' => $msg->getId(), 'snippet' => $msg->getSnippet()]);
//                         $this->info("Fetched Email for User ID {$userId}: " . $msg->getSnippet());
//                     }
//                 }
//             } catch (\Exception $e) {
//                 Log::error("Error fetching Gmail emails for user ID {$userId}: " . $e->getMessage());
//                 $this->error("Error for User ID {$userId}: " . $e->getMessage());
//             }
//         }
//     }
// }

class FetchGmailEmails extends Command
{
    protected $signature = 'gmail:fetch';
    protected $description = 'Fetch emails from Gmail for all users with valid tokens';

    public function __construct()
    {
        parent::__construct();
    }

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
                $messages = $service->users_messages->listUsersMessages($user, ['maxResults' => 5]);

                if (count($messages->getMessages()) == 0) {
                    $this->info("No emails found for user ID: {$userId}.");
                } else {
                    foreach ($messages->getMessages() as $message) {
                        // $msg = $service->users_messages->get($user, $message->getId());

                        // Log::info("Email for User ID {$userId}: ", ['id' => $msg->getId(), 'snippet' => $msg->getSnippet()]);
                        // $this->info("Fetched Email for User ID {$userId}: " . $msg->getSnippet());
                        $msg = $service->users_messages->get($user, $message->getId(), ['format' => 'full']);


                        $payload = $msg->getPayload();
                        $headers = $payload->getHeaders();

                        $fromEmail = $toEmail = $subject = '';

                        foreach ($headers as $header) {
                            if ($header->getName() == 'From') {
                                $fromEmail = $header->getValue();
                            }
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
                                if ($part['mimeType'] === 'text/plain' || $part['mimeType'] === 'text/html') {
                                    $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $part['body']['data']));
                                    break;
                                }
                            }
                        }
                        preg_match('/\b\d{10}\b/', $body, $matches);
                        $phoneNumber = $matches[0] ?? null;


                        $client = null;
                        if ($phoneNumber) {
                            $client = Client::where('phone', $phoneNumber)->first();
                        }

                        if ($client) {
                            $this->info("Matched Client: {$client->firstname}, Phone: {$client->phone}");
                            ScheduleChange::create([
                                'user_type' => Client::class,
                                'reason' => 'urgent hiring',
                                'comments' => $body,
                                'user_id' => $client->id,
                                'status' => 'pending',
                                'team_response' => null,
                            ]);
                        } else {
                            $this->warn("No client found for phone number: {$phoneNumber}");
                        }
                        // $this->info("Fetched Email for User ID {$userId}: 
                        // From: {$fromEmail}
                        // To: {$toEmail}
                        // Subject: {$subject}
                        // Body: {$body}");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error fetching Gmail emails for user ID {$userId}: " . $e->getMessage());
                $this->error("Error for User ID {$userId}: " . $e->getMessage());
            }
        }
    }
}
