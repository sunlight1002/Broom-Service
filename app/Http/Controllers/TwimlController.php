<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Client;
use Twilio\Rest\Client as TwilioClient;
use App\Enums\SettingKeyEnum;
use App\Models\Setting;
use App\Models\Schedule;
use App\Traits\ScheduleMeeting;
use App\Traits\GoogleAPI;
use App\Jobs\SendMeetingMailJob;
use App\Events\ClientLeadStatusChanged;
use App\Enums\LeadStatusEnum;
use App\Models\Notification;
use App\Enums\NotificationTypeEnum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;



class TwimlController extends Controller
{
    use ScheduleMeeting, GoogleAPI;
    
    protected $lang = 'en';
    protected $twilioAccountSid;
    protected $twilioAuthToken;
    protected $twilioWhatsappNumber;
    protected $twilio;
    protected $twilioWebhook;

    public function __construct()
    {
        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');
        $this->twilioWebhook = config("services.twilio.webhook");

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    public function index(Request $request)
    {
        $attempt = (int) $request->input('attempt', 1); // default = 1
        $maxAttempts = 3;
    
        $response = new VoiceResponse();
    
        if ($attempt > $maxAttempts) {

            $called = $request->input('Called');
            $phone = str_replace("+", "", $called);
            $client = Client::where('phone', 'like', '%' . $phone . '%')->first();
            if (!$client) {
                $client = $this->createLead($client,$phone);
            }

            $client->lead_status()->updateOrCreate(
                [],
                ['lead_status' => LeadStatusEnum::UNANSWERED]
            );
            $client->status = 0;
            $client->save();

            event(new ClientLeadStatusChanged($client, LeadStatusEnum::UNANSWERED));

            $response->say(
                $this->lang === 'he'
                    ? 'לא התקבל קלט. השיחה תסתיים כעת.'
                    : 'No input received. The call will now end.',
                $this->getLocale()
            );
            $response->hangup();
            return response($response)->header('Content-Type', 'application/xml');
        }
    
        $gather = $response->gather([
            'numDigits' => 1,
            'action' => url($this->twilioWebhook . 'api/twiml/handleSelection?lang=' . $this->lang),
            'timeout' => 7
        ]);
    
        $message = $this->getInitialMessage();
        $gather->say($message, $this->getLocale());
    
        // Retry with incremented attempt count
        $response->redirect(
            url($this->twilioWebhook . 'api/twiml?lang=' . $this->lang . '&attempt=' . ($attempt + 1))
        );
    
        return response($response)->header('Content-Type', 'application/xml');
    }
    


    protected function handleSelection(Request $request)
    {
        $this->lang = $request->input('lang', $this->lang);

        try {
            $digits = $request->input('Digits');
            $response = new VoiceResponse();
        
            // Response text based on user selection and static language
            switch ($digits) {
                case '1':
                    $m = [
                        "en" => "We offer cleaning services tailored to your needs, whether for regular visits or a one-time deep clean. Unlike alternatives like hourly cleaners or manpower services, we provide fixed-price packages that include all necessary social benefits, travel expenses, and quality assurance from our supervisors. To ensure the best fit for your needs, we start with an on-site meeting. It’s free, takes about 10-15 minutes, and allows us to assess your needs and provide a detailed quote. Would you like to schedule a meeting?,  press 5 to schedule a meeting, or Press 9 return to main menu",
                        "he" => "אנו מציעים שירותי ניקיון המותאמים לצרכים שלך, בין אם לביקורים קבועים או לניקיון עמוק חד פעמי. בניגוד לחלופות כמו מנקים לפי שעה או שירותי כוח אדם, אנו מספקים חבילות במחיר קבוע הכוללות את כל ההטבות הסוציאליות הנדרשות, הוצאות הנסיעה והבטחת איכות מהממונים שלנו. כדי להבטיח את ההתאמה הטובה ביותר לצרכים שלך, אנו מתחילים בפגישה במקום. זה בחינם, 0-1 דקות לצרכים שלך, לוקח לנו בערך 0-1 דקות. ציטוט מפורט האם תרצה לקבוע פגישה?, הקש 5 כדי לקבוע פגישה, או הקש 9 חזור לתפריט הראשי",
                        ];

                    $response->say(
                        $this->lang === 'he' ? $m['he'] : $m['en'],
                        $this->getLocale()
                    );
                
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'. '?lang=' . $this->lang), // Ensure this is your correct URL
                        'timeout' => 7
                    ]);

                    break;
                case '2':
                    $m = [
                        "en" => "We specialize in office maintenance with fixed pricing, based on the frequency and scope of cleaning needed. Our services can include general cleaning, detailed maintenance, and additional services as required. To tailor a solution for your office, we’d schedule a free meeting at your location. It’s quick and ensures we understand your needs and can provide an accurate quote. Would you like to book a meeting?, press 5 to schedule a meeting, or Press 9 return to main menu",
                        "he" => "אנו מתמחים בתחזוקת משרדים בתמחור קבוע, על בסיס תדירות והיקף הניקיון הנדרשים. השירותים שלנו יכולים לכלול ניקיון כללי, תחזוקה מפורטת ושירותים נוספים לפי הצורך. כדי להתאים פתרון למשרד שלך, נקבע פגישה חינם במיקום שלך. זה מהיר ומבטיח שאנו מבינים את הצרכים שלך ונוכל לספק הצעת מחיר מדויקת. האם תרצה להזמין פגישה חוזרת לתפריט 9, הקש פגישה, או לחץ על לוח פגישה ראשי?"
                        ];

                    $response->say(
                        $this->lang === 'he' ? $m['he'] : $m['en'],
                        $this->getLocale()
                    );
                
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'. '?lang=' . $this->lang), // Ensure this is your correct URL
                        'timeout' => 7
                    ]);

                    break;
    
                case '3':
                    $m = [
                        "en" => "For specialized services like post-renovation cleaning, window cleaning, or floor polishing, we assess each job individually.
                        One of our supervisors can meet you on-site to review the details and provide a professional recommendation and a detailed quote. The meeting is free and without obligation. Would you like to schedule a meeting?, Press 5 to schedule a meeting, or Press 9 return to main menu",
                        "he" => "עבור שירותים מיוחדים כמו ניקוי לאחר שיפוץ, ניקוי חלונות או פוליש לרצפה, אנו מעריכים כל עבודה בנפרד. אחד מהמפקחים שלנו יכול לפגוש אותך במקום לעיון בפרטים ומתן המלצה מקצועית והצעת מחיר מפורטת. המפגש ללא תשלום וללא התחייבות. האם תרצה לקבוע פגישה?, הקש 5 כדי לקבוע פגישה, או הקש 9 חזרה לתפריט הראשי",
                        ];

                    $response->say(
                        $this->lang === 'he' ? $m['he'] : $m['en'],
                        $this->getLocale()
                    );
                
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'. '?lang=' . $this->lang), // Ensure this is your correct URL
                        'timeout' => 7
                    ]);

                    break;

                case '4':
                    $m = [
                        "en" => "Our pricing is based on a fixed cost per visit, not an hourly rate.
                                This covers the worker’s wage, social benefits, travel, insurance, and the support of a professional company.
                                If you need a rough estimate, the average cost is around 100 shekels per hour (excluding VAT).
                                However, please note this reflects a comprehensive service, not simply a worker’s hourly wage.
                                Press 8 to Price is expensive, or Press 9 return to main menu",
                        "he" => "התמחור שלנו מבוסס על עלות קבועה לביקור, לא על תעריף שעתי.
                                זה מכסה את שכרו של העובד, הטבות סוציאליות, נסיעות, ביטוח ותמיכה של חברה מקצועית.
                                אם צריך הערכה גסה, העלות הממוצעת נעה סביב 100 שקלים לשעה (לא כולל מע).
                                עם זאת, שימו לב שזה משקף שירות מקיף, לא רק שכר שעתי של עובד.
                                הקש 8 כדי שהמחיר יקר, או הקש 9 חזור לתפריט הראשי",
                        ];

                    $response->say(
                        $this->lang === 'he' ? $m['he'] : $m['en'],
                        $this->getLocale()
                    );
                
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/expensiveAndBack'. '?lang=' . $this->lang), // Ensure this is your correct URL
                        'timeout' => 7
                    ]);

                    break;

                case '5':
                    $called = $request->input('Called');
                    $phone = str_replace("+", "", $called);
                    $client = Client::where('phone', 'like', '%' . $phone . '%')->first();
                    if (!$client) {
                        $client = $this->createLead($client,$phone);
                    }

                    $nextAvailableSlot = $this->nextAvailableMeetingSlot();
                    if ($nextAvailableSlot) {
                        $address = $client->property_addresses()->first();

                        $scheduleData = [
                            'address_id'    => $address->id,
                            'booking_status'    => 'pending',
                            'client_id'     => $client->id,
                            'meet_via'      => 'on-site',
                            'purpose'       => 'Price offer',
                            'start_date'    =>  $nextAvailableSlot['date'],
                            'start_time_standard_format' =>  $nextAvailableSlot['start_time'],
                            'team_id'       => $nextAvailableSlot['team_member_id']
                        ];

                        $scheduleData['start_time'] = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $nextAvailableSlot['start_time'])->format('h:i A');
                        $scheduleData['end_time'] = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $nextAvailableSlot['start_time'])->addMinutes(30)->format('h:i A');

                        $schedule = Schedule::create($scheduleData);

                            $client->lead_status()->updateOrCreate(
                                [],
                                ['lead_status' => LeadStatusEnum::POTENTIAL]
                            );
                            $client->status = 1;
                            $client->save();

                            event(new ClientLeadStatusChanged($client, LeadStatusEnum::POTENTIAL));

                            $googleAccessToken = Setting::query()
                                ->where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)
                                ->value('value');

                            if ($googleAccessToken) {
                                $schedule->load(['client', 'team', 'propertyAddress']);

                                try {
                                    \Log::info("dsdsdds");
                                    // Initializes Google Client object
                                    $googleClient = $this->getClient();

                                    $this->saveGoogleCalendarEvent($schedule);

                                    // $this->sendMeetingMail($schedule);
                                    // SendMeetingMailJob::dispatch($schedule);
                                } catch (\Throwable $th) {
                                    \Log::info($th);
                                }
                            }

                            Notification::create([
                                'user_id' => $schedule->client_id,
                                'user_type' => get_class($client),
                                'type' => NotificationTypeEnum::SENT_MEETING,
                                'meet_id' => $schedule->id,
                                'status' => $schedule->booking_status
                            ]);

                            $sid = $client->lng == "heb" ? "HX89aec71b6f4c192905a2925dcffdc05d" : "HX706359f321c9255564b901087e0758e7";

                            $twi = $this->twilio->messages->create(
                                "whatsapp:+$phone",
                                [
                                    "from" => $this->twilioWhatsappNumber, 
                                    "contentSid" => $sid,
                                    "contentVariables" => json_encode([
                                        "1" => "meeting-status/" . base64_encode($schedule->id) . "/reschedule"
                                    ]),
                                ]
                                );
                            \Log::info($twi->sid);
                        }

                        $m = [
                            "en" => "Please choose a time slot for your appointment using the link which i send on your whatsapp.",
                            "he" => "אנא בחר משבצת זמן לפגישה שלך באמצעות הקישור שאני שולח בוואטסאפ שלך.",
                            ];

                        $response->say(
                            $this->lang === 'he' ? $m['he'] : $m['en'],
                            $this->getLocale()
                        );
                    
                        $response->hangup();

                    break;

                case '6':
                    $message = $this->lang === 'he' ? "אנא אמור את שם העיר שלך?" : "Please say your city name?";
                    // $response->say($message,$this->getLocale());
                
                    $gather = $response->gather([
                        'input' => 'speech',
                        'action' => url($this->twilioWebhook . 'api/twiml/verifyArea'. '?lang=' . $this->lang), // URL to handle the speech input
                        'timeout' => 7,
                        'hints' => 'name',
                        'speechTimeout' => 'auto', // Allow for automatic timeout
                    ]);

                    $gather->say($message, $this->getLocale()); // move `say()` inside gather

                    break;

                case '7':
                    $called = $request->input('Called');
                    $phone = str_replace("+", "", $called);
                    $client = Client::where('phone', 'like', '%' . $phone . '%')->first();
                    if (!$client) {
                        $client = $this->createLead($client,$phone);
                    }

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::UNINTERESTED]
                    );
                    $client->status = 0;
                    $client->save();

                    event(new ClientLeadStatusChanged($client, LeadStatusEnum::UNINTERESTED));

                    $m = [
                        "en" => "Thank you for your time. If you ever need cleaning services in the future, please feel free to reach out. Have a great day!",
                        "he" => "תודה על הזמן שהקדשת. אם אי פעם תזדקק לשירותי ניקיון בעתיד, אנא אל תהסס לפנות. שיהיה לך יום נהדר!",
                        ];

                    $response->say(
                        $this->lang === 'he' ? $m['he'] : $m['en'],
                        $this->getLocale()
                    );

                    $response->hangup();

                    break;


                case '8':
                    // Toggle the language
                    $this->lang = $this->lang === 'he' ? 'en' : 'he';
                    $message = $this->getInitialMessage();

                    // Replay the initial menu in the new language
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'. '?lang=' . $this->lang),
                        'timeout' => 7
                    ]);

                    $gather->say($message, $this->getLocale()); // move `say()` inside gather

                    break;

                case '9':

                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection?lang=' . $this->lang),
                        'timeout' => 7
                    ]);
                
                    $message = $this->getInitialMessage();
                    $gather->say($message, $this->getLocale());
                
                    // Optional: If user doesn't press anything, repeat
                    $response->redirect(url($this->twilioWebhook . 'api/twiml'));

                    break;
                    
            }
        
            return response($response)->header('Content-Type', 'application/xml');
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    public function handleInitialInput(Request $request)
    {
        $this->lang = $request->input('lang', $this->lang);

        try {
            $digits = $request->input('Digits');
            \Log::info($digits);
            $response = new VoiceResponse();
        
            // Response text based on user selection and static language
            switch ($digits) {
                case '1':
                    $message = $this->lang === 'he' ? "כדי להבטיח שנוכל לעזור, תוכל בבקשה להודיע ​​לי באיזו עיר אתה נמצא?": "To ensure we can help, could you please let me know which city you’re located in?";
                    // $response->say($message,$this->getLocale());
                
                    $gather = $response->gather([
                        'input' => 'speech',
                        'action' => url($this->twilioWebhook . 'api/twiml/verifyArea'. '?lang=' . $this->lang), // URL to handle the speech input
                        'timeout' => 7,
                        'hints' => 'name',
                        'speechTimeout' => 'auto', // Allow for automatic timeout
                    ]);

                    $gather->say($message, $this->getLocale()); // move `say()` inside gather

                    break;

                case '2':
                    $called = $request->input('Called');
                    $countKey = 'press_2_count_' . $called;
                    $count = Cache::get($countKey, 0) + 1;
                    Cache::put($countKey, $count, now()->addMinutes(10));
                    if ($count > 3) {
                        Cache::forget($countKey);
                        $response->hangup();
                        return response($response)->header('Content-Type', 'application/xml');
                    }
                
                     // Then repeat the original menu message
                     $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleInitialInput'. '?lang=' . $this->lang),
                        'timeout' => 7
                    ]);
                
                    $menuPrompt = $this->lang === 'he'
                        ? "אנו מתמחים בשירותי ניקיון איכותיים, לרבות ניקיון ביתי רגיל, ניקיון עמוק חד פעמי, תחזוקת משרדים ומשימות ספציפיות כמו ניקיון או פוליש לאחר שיפוץ. כדי לסייע לך טוב יותר, תוכל בבקשה להבהיר איזה סוג של שירות אתה מחפש? האם אתה מחפש שירותי ניקיון לבית, למשרד או לפרויקט ספציפי? אם כן, לחץ על 1. אם לא, לחץ על 2. אם לא, לחץ על 7."
                        : "We specialize in high-quality cleaning services, including regular home cleaning, one-time deep cleans, office maintenance, and specific tasks like post-renovation cleaning or polishing. To better assist you, could you please clarify what kind of service you're looking for? Are you looking for cleaning services for your home, office, or a specific project? If yes, press 1. If no, press 2. To continue in Hebrew, press 7.";
                
                    $gather->say($menuPrompt, $this->getLocale());

                    break;
                    
    
                case '7':
                    // Toggle the language
                    $this->lang = $this->lang === 'he' ? 'en' : 'he';
                    $message = $this->getInitialMessage();

                    // Replay the initial menu in the new language
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleInitialInput'. '?lang=' . $this->lang),
                        'timeout' => 7
                    ]);

                    $gather->say($message, $this->getLocale()); // move `say()` inside gather

                    break;
                    
                default:
                // Add a message to inform user about invalid input
                if(!empty($digits)){
                    $response->say(
                        $this->lang === 'he'
                        ? 'הקלט לא היה תקין. בבקשה נסה שוב.'
                        : 'Invalid input. Please try again.',
                        $this->getLocale()
                    );
                }else{
                    $response->say(
                        $this->lang === 'he'
                            ? 'אין קלט. בבקשה נסה שוב.'
                            : 'No input. Please try again.',
                        $this->getLocale()
                    );
                    
                }
            
                $gather = $response->gather([
                    'numDigits' => 1,
                    'action' => url($this->twilioWebhook . 'api/twiml/handleSelection?lang=' . $this->lang),
                    'timeout' => 3
                ]);
            
                $message = $this->getInitialMessage();
                $gather->say($message, $this->getLocale());

                break;
                
            }
        
            return response($response)->header('Content-Type', 'application/xml');
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    // Helper methods to get messages based on language
    protected function getInitialMessage()
    {
        $messages = [
            'en' => "Hello, my name is Bar, and I’m calling from Broom Service. Thank you for reaching out to us. 
                    Press 1 for Home Cleaning (Recurring or One-Time),
                    Press 2 for Office Cleaning,
                    Press 3 for Specific Projects (Post-Renovation, Polishing, etc.),
                    Press 4 for Pricing Information,
                    Press 5 to Schedule a Meeting,
                    Press 6 to Check Our Service Area,
                    Press 7 if you are not interested,
                    Press 8 ,To continue in Hebrew.",
            'he' => "שלום, שמי בר, ​​ואני מתקשר משירות מטאטא. תודה שפנית אלינו.
                    הקש 1 לניקוי הבית (חוזר או חד פעמי),
                    הקש 2 לניקוי משרדים,
                    הקש 3 לפרויקטים ספציפיים (אחרי שיפוץ, ליטוש וכו'),
                    לחץ על 4 למידע על תמחור,
                    הקש 5 כדי לקבוע פגישה,
                    הקש 6 כדי לבדוק את אזור השירות שלנו,
                    הקש 7 אם אתה לא מעוניין,
                    הקש 8 ,כדי להמשיך באנגלית."
        ];
        return $messages[$this->lang] ?? $messages['en'];
    }

    protected function getLocale()
    {
        return $this->lang === 'he' ? ['language' => 'he-IL', 'voice' => 'Google.he-IL-Standard-A'] : ['language' => 'en-US', 'voice' => 'Polly.Joanna'];
    }

    protected function verifyArea(Request $request)
    {
        $this->lang = $request->input('lang', $this->lang);

        $response = new VoiceResponse();
        
        // 1. Get the speech input
        $speechResult = $request->input('SpeechResult');
        \Log::info('User provided area: ' . $speechResult);
    
        // 2. Call Google Maps API
        $googleResponse = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $speechResult,
            'key' => config('services.google.map_key'),
            'language' => $this->lang === 'he' ? 'he' : 'en',
        ]);
    
        // 3. Check if response is valid
        if ($googleResponse->successful()) {
            $data = $googleResponse->object();
            $result = $data->results[0] ?? null;
    
            if ($result && isset($result->formatted_address)) {
                $resolvedAddress = $result->formatted_address;
                \Log::info('Resolved address: ' . $resolvedAddress);
    
                // 4. Define your service areas
                $areas = [
                    'Tel Aviv', 'Ramat Gan', 'Givatayim', 'Kiryat Ono',
                    'Ganei Tikva', 'Ramat HaSharon', 'Kfar Shmaryahu',
                    'Rishpon', 'Herzliya',
                    'תל אביב', 'רמת גן', 'גבעתיים', 'קרית אונו',
                    'גני תקווה', 'רמת השרון', 'כפר שמריהו', 'רשפון', 'הרצליה',
                ];
    
                // 5. Match resolved address with supported areas
                $matched = collect($areas)->first(function ($area) use ($resolvedAddress) {
                    return str_contains($resolvedAddress, $area);
                });
    
                if ($matched) {
                    $m = [
                        "en" => "Great, we provide services in your area! Press 9 to return to the main menu, or 5 to schedule a meeting.",
                        "he" => "מצוין, אנו מספקים שירותים באזור שלך! הקש 9 כדי לחזור לתפריט הראשי, או 5 כדי לקבוע פגישה.",
                        ];

                    $response->say(
                        $this->lang === 'he' ? $m['he'] : $m['en'],
                        $this->getLocale()
                    );

                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'. '?lang=' . $this->lang), // Ensure this is your correct URL
                        'timeout' => 7
                    ]);

                } else {
                    $called = $request->input('Called');
                    $phone = str_replace("+", "", $called);
                    $client = Client::where('phone', 'like', '%' . $phone . '%')->first();
                    if (!$client) {
                        $m = [
                            "en" => "Sorry, we can't find your number in our database. Please try
                            to contact us through our website or by phone at 03-566-4444.",
                            "he" => "",
                            ];
                            $response->say($this->lang === 'he' ? $m['he'] : $m['en'], $this->getLocale());
                            $response->hangup();
                    }

                    $client->lead_status()->updateOrCreate(
                        [],
                        ['lead_status' => LeadStatusEnum::IRRELEVANT]
                    );
                    $client->status = 0;
                    $client->save();

                    event(new ClientLeadStatusChanged($client, LeadStatusEnum::IRRELEVANT));

                    $response->say(
                        $this->lang === 'he' ? "לצערי, אנחנו לא מספקים כרגע שירותים באזור שלך. עם זאת, אשמח לשמור את הפרטים שלך ולהודיע ​​לך אם נתרחב למיקום שלך בעתיד. תודה על פנייתך!"
                         : "Unfortunately, we don’t currently provide services in your area. However, I’d be happy to save your details and notify you if we expand to your location in the future. Thank you for reaching out!",
                        $this->getLocale()
                    );
                    $response->hangup(); // End the call
                    return $response;
                }
            } else {
                \Log::warning("Google API returned no results.");
            }
        } else {
            \Log::error('Google Maps API failed: ' . $googleResponse->body());
        }
    
        return response($response)->header('Content-Type', 'application/xml');
    }


    protected function expensiveAndBack(Request $request)
    {
        $this->lang = $request->input('lang', $this->lang);

        try {
            $digits = $request->input('Digits');
            $response = new VoiceResponse();
        
            // Response text based on user selection and static language
            switch ($digits) {
                case '8':
                    $m = [
                        "en" => "I understand your concern. It’s important to compare properly:If you compare us to another company, make sure they’re a registered service contractor and provide official invoices.If you compare us to a private cleaner, note that they already charge around 70–80 shekels per hour, and legal employment (with social benefits, pension, etc.) from day one will raise that cost significantly.With us, you get peace of mind at a fixed rate, without any legal or administrative burden.  Press 5 to schedule a meeting, or Press 9 return to main menu",
                        "he" => "אני מבין את הדאגה שלך. חשוב להשוות נכון: אם אתה משווה אותנו לחברה אחרת, וודא שהם קבלן שירות רשום ותספק חשבוניות רשמיות. אם אתה משווה אותנו למנקה פרטית, שים לב שהם כבר גובים בסביבות 70–80 שקל לשעה, והעסקה חוקית (עם הטבות סוציאליות, פנסיה וכו') מהיום הראשון תעלה לנו את המחיר הזה, בלי שום עלות חוקית או שקט קבוע. עומס אדמיניסטרטיבי לחץ על 5 כדי לקבוע פגישה, או לחץ על 9 חזרה לתפריט הראשי."
                        ];

                    $response->say(
                        $this->lang === 'he' ? $m['he'] : $m['en'],
                        $this->getLocale()
                    );
                
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'. '?lang=' . $this->lang), // Ensure this is your correct URL
                        'timeout' => 7
                    ]);

                    break;

                case '9':
                    $gather = $response->gather([
                        'numDigits' => 1,
                        'action' => url($this->twilioWebhook . 'api/twiml/handleSelection?lang=' . $this->lang),
                        'timeout' => 7
                    ]);
                
                    $message = $this->getInitialMessage();
                    $gather->say($message, $this->getLocale());
                
                    // Optional: If user doesn't press anything, repeat
                    $response->redirect(url($this->twilioWebhook . 'api/twiml'));

                    break;

                }
                return response($response)->header('Content-Type', 'application/xml');
            } catch (\Throwable $th) {
                return response()->json(['error' => $th->getMessage()], 500);
            }
    }


    public function createLead($client,$phone){
        $client                = new Client;
        $client->firstname     = '';
        $client->lastname      = '';
        $client->phone         = $phone;
        $client->email         = "";
        $client->status        = 0;
        $client->password      = Hash::make($phone);
        $client->passcode      = $phone;
        $client->geo_address   = '';
        $client->lng           = ($this->lang == 'heb' ? 'heb' : 'en');
        $client->save();
        return response()->json($client);
    }
    


    // protected function serviceAreas()
    // {
    //     $messages = [
    //         'en' => "We provide service in the following areas:,
    //                 - Tel Aviv,
    //                 - Ramat Gan,
    //                 - Givatayim,
    //                 - Kiryat Ono,
    //                 - Ganei Tikva,
    //                 - Ramat HaSharon,
    //                 - Kfar Shmaryahu,
    //                 - Rishpon,
    //                 - Herzliya,",
    //         'he' => "אנו מספקים שירות בתחומים הבאים:,
    //                 - תל אביב,
    //                 - רמת גן,
    //                 - גבעתיים,
    //                 - קרית אונו,
    //                 - גני תקווה,
    //                 - רמת השרון,
    //                 - כפר שמריהו,
    //                 - רשפון,
    //                 - הרצליה",
    //     ];
    //     return $messages[$this->lang] ?? $messages['en'];
    // }

    // protected function getAppointmentInfo()
    // {
    //     $messages = [
    //         'en' => "To receive a quote, please send us messages with the following details\n\nPlease send your full name",
    //         'he' => "כדי לקבל הצעת מחיר, אנא שלחו את הפרטים הבאים: 📝\n\nשם מלא",
    //     ];
    //     return $messages[$this->lang] ?? $messages['en'];
    // }

    // protected function getCustomerServiceInfo()
    // {
    //     $messages = [
    //         'en' => 'Existing customers can use our customer portal to get information, make changes to orders, and contact us on various matters.
    //                 You can also log in to our customer portal with the details you received at the time of registration at crm.broomservice.co.il.
    //                 Enter your phone number or email address with which you registered for the service 📝',
    //         'he' => 'לקוחות קיימים יכולים להשתמש בפורטל הלקוחות שלנו כדי לקבל מידע, לבצע שינויים בהזמנות וליצור איתנו קשר בנושאים שונים.
    //                 תוכלו גם להיכנס לפורטל הלקוחות שלנו עם הפרטים שקיבלתם במעמד ההרשמה בכתובת crm.broomservice.co.il.
    //                 הזן את מס הטלפון או כתובת המייל איתם נרשמת לשירות 📝',
    //     ];
    //     return $messages[$this->lang] ?? $messages['en'];
    // }

    // protected function getHumanRepresentativeInfo()
    // {
    //     $messages = [
    //         'en' => 'Dear customers, office hours are Monday-Thursday from 8:00 to 14:00.
    //                 If you contact us outside of business hours, a representative from our team will get back to you as soon as possible on the next business day, during business hours.
    //                 If you would like to speak to a human representative, please send a message with the word "Human Representative". 🙋🏻',
    //         'he' => 'לקוחות יקרים, שעות הפעילות במשרד הן בימים א-ה בשעות 8:00-14:00.
    //                 במידה ופניתם מעבר לשעות הפעילות נציג מטעמנו יחזור אליכם בהקדם ביום העסקים הבא, בשעות הפעילות.
    //                 אם אתם מעוניינים לדבר עם נציג אנושי, אנא שלחו הודעה עם המילה "נציג אנושי". 🙋🏻',
    //     ];
    //     return $messages[$this->lang] ?? $messages['en'];
    // }

    // protected function getLangMenu()
    // {
    //     $this->lang = $this->lang === 'he' ? 'en' : 'he';

    //     $response = new VoiceResponse();
    
    //     // Initial message based on the static language
    //     $message = $this->getInitialMessage();
    //     $response->say($message,$this->getLocale());
    
    //     // Gather input for the user's selection
    //     $gather = $response->gather([
    //         'numDigits' => 1,
    //         'action' => url($this->twilioWebhook . 'api/twiml/handleInitialInput'),
    //         'timeout' => 7
    //     ]);
    
    //     return response($response)->header('Content-Type', 'application/xml');
    // }



    // public function handleName(Request $request)
    // {
    //     $response = new VoiceResponse();
    //     $speechResult = $request->input('SpeechResult'); // The recognized speech

    //     // Log the name
    //     // You can replace this with your preferred logging or storage method
    //     \Log::info('User provided name: ' . $speechResult);

    //     // Acknowledge receipt of the name and end the call or proceed as needed
    //     $response->say('Thank you! We have received your name. We will contact you shortly.');

    //     // You might want to end the call or redirect to another endpoint
    //     $response->hangup(); // End the call

    //     return response($response)->header('Content-Type', 'application/xml');
    // }



    // public function handleLanguage(Request $request)
    // {
    //     $digits = $request->input('Digits');
    //     $response = new VoiceResponse();
    
    //     // Response text based on user selection and static language
    //     switch ($digits) {
    //         case '1':
    //             $response->say($this->getServiceInfo(), $this->getLocale());
    //             $gather = $response->gather([
    //                 'numDigits' => 1,
    //                 'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'. '?lang=' . $this->lang), // Ensure this is your correct URL
    //                 'timeout' => 7
    //             ]);
    //             $menuPrompt = $this->lang === 'he'
    //                         ? 'לחץ 3 לתיאום פגישה להצעת מחיר או 5 לשיחה עם נציג.'
    //                         : 'Press 3 to schedule an appointment for a quote or 5 to speak with a representative.';
            
    //             $gather->say($menuPrompt, $this->getLocale());

    //             break;
    //         case '2':
    //             $response->say($this->getServiceAreas(), $this->getLocale());
    //             $gather = $response->gather([
    //                 'numDigits' => 1,
    //                 'action' => url($this->twilioWebhook . 'api/twiml/handleSelection'), // Ensure this is your correct URL
    //                 'timeout' => 7
    //             ]);
    //             $menuPrompt = $this->lang === 'he'
    //                         ? 'לחץ 3 לתיאום פגישה להצעת מחיר או 5 לשיחה עם נציג.'
    //                         : 'Press 3 to schedule an appointment for a quote or 5 to speak with a representative.';
            
    //             $gather->say($menuPrompt, $this->getLocale());
    //             break;
    //         case '3':

    //             $response->say($this->getAppointmentInfo(), $this->getLocale());
    //             $gather = $response->gather([
    //                 'input' => 'speech',
    //                 'action' => url($this->twilioWebhook . 'api/twiml/handleName'), // URL to handle the speech input
    //                 'timeout' => 7,
    //                 'hints' => 'name',
    //                 'speechTimeout' => 'auto', // Allow for automatic timeout
    //             ]);

    //             $menuPrompt = $this->lang === 'he' ? 'בבקשה אמור את שמך המלא אחרי הצפצוף.' : 'Please say your full name after the beep.';
    //             $gather->say($menuPrompt, $this->getLocale());
                
    //             break;
    //         case '4':
    //             $response->say($this->getCustomerServiceInfo(), $this->getLocale());
    //             break;
    //         case '5':
    //             $response->say($this->getHumanRepresentativeInfo(),  $this->getLocale());
    //             break;
    //         case '7':
    //             $response->say($this->getLangMenu(), $this->getLocale());
    //             break;
    //         case '9':
    //             $response->redirect(url($this->twilioWebhook . 'api/twiml'));
    //             break;
    //         case '0':
    //             $response->redirect(url($this->twilioWebhook . 'api/twiml'));
    //             break;
    //         default:

    //             $menuPrompt = $this->lang === 'he'
    //             ? 'בבקשה, לא הבנתי את הבחירה שלך.' : 'Sorry, I did not understand that choice.';
     
    //             $response->say($menuPrompt, $this->getLocale());
    //             $response->redirect(url($this->twilioWebhook . 'api/twiml'));
    //             break;
    //     }
    
    //     return response($response)->header('Content-Type', 'application/xml');
    // }

    // public function handleSelection(Request $request)
    // {
    //     $digits = $request->input('Digits');
    //     $response = new VoiceResponse();
    
    //     switch ($digits) {
    //         case '3':
    //             $menuPrompt = $this->lang === 'he'
    //             ? 'בחרת לתאם פגישה להצעת מחיר. אנא המתן בעוד אנו מחברים אותך לנציג.'
    //             : 'You have chosen to schedule an appointment for a quote. Please wait while we connect you to a representative.';
            
    //             $response->say($menuPrompt, $this->getLocale());
            
    //             // Redirect or forward to the appropriate action for scheduling an appointment
    //             break;
    //         case '5':
    //                 $menuPrompt = $this->lang === 'he'
    //                 ? 'אתה מחובר עכשיו לנציג. נא להחזיק.'
    //                 : 'You will now be connected to a representative. Please hold.';
            
    //                 $response->say($menuPrompt, $this->getLocale());
            
    //             // Redirect or forward to the appropriate action for speaking with a representative
    //             break;
    //         default:
    //                 $menuPrompt = $this->lang === 'he'
    //                     ? 'סליחה, לא הבנתי את הבחירה שלך. אנא הקש 3 לתיאום פגישה או 5 לשיחה עם נציג.'
    //                     : 'Sorry, I did not understand that choice. Please press 3 to schedule an appointment or 5 to speak with a representative.';

    //                 $response->say($menuPrompt, $this->getLocale());

    //             $response->redirect(url($this->twilioWebhook . 'api/twiml'));
    //             break;
    //     }
    
    //     return response($response)->header('Content-Type', 'application/xml');
    // }

}
