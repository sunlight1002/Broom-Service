<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Http\Response;


class LeadTwilioController extends Controller
{
    protected $botMessages = [
        'main-menu' => [
            'en' => "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today?  At any stage, you can return to the main menu by press the number 9 or return one menu back by press the number 0, press 1 About the Service, press 2 for Service Areas, press 3 for Set an appointment for a quote, press 4 for Customer Service press 5 Switch to a human representative (during business hours) press 7 for שפה עברית",
            'heb' => 'היי, אני בר, ​​הנציגה הדיגיטלית של Broom Service. איך אני יכול לעזור לך היום?  בכל שלב ניתן לחזור לתפריט הראשי ע"י לחיצה על הספרה 9 או להחזיר תפריט אחד אחורה ע"י לחיצה על הספרה 0, הקש 1 אודות השירות, הקש 2 לאזורי שירות, הקש 3 לקבע פגישה להצעת מחיר, הקש 4 לשירות לקוחות הקש 5 עבור לנציג אנושי (בשעות העבודה)
                    press 6. English menu'
        ],
    ];

    protected $twilioClient;

    public function __construct()
    {
        $this->twilioClient = new Client(config('services.twilio.twilio_id'), config('services.twilio.twilio_token'));
    }

    public function initiateCall(Request $request)
    {
        $to = "+919904114252";
        $from = config('services.twilio.twilio_number');
        $url =  'https://2fdf-152-58-60-9.ngrok-free.app/api/twilio/handle-call';

        try {
            $call = $this->twilioClient->calls->create($to, $from, ['url' => $url]);
            return response()->json(['message' => 'Call initiated', 'call_sid' => $call->sid]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleCall()
    {
        $response = new VoiceResponse();
        $gather = $response->gather([
            'numDigits' => 1,
            'action' => 'https://2fdf-152-58-60-9.ngrok-free.app/api/twilio/handle-language',
            'method' => 'POST'
        ]);
        $gather->say("Press 1 for English. Press 2 for Hebrew.", ['voice' => 'alice', 'language' => 'en-IN']);

        return response($response, 200)->header('Content-Type', 'text/xml');

    }

    public function handleLanguage(Request $request)
    {
        $digit = $request->input('Digits');
        $response = new VoiceResponse();

        switch ($digit) {
            case '1':
                $response->say("You have selected english", ['voice' => 'alice','language' => 'en-IN']); 
                $response->redirect('https://2fdf-152-58-60-9.ngrok-free.app/api/twilio/handle-call-flow?lang=en');
                break;
            case '2':
                $response->say("בחרת עברית.", ['voice' => 'alice', 'language' => 'he-IL']);
                $response->redirect('https://2fdf-152-58-60-9.ngrok-free.app/api/twilio/handle-call-flow?lang=heb');
                break;
            default:
                $response->say("Invalid input. Please try again.", ['voice' => 'alice']);
                $response->redirect('https://2fdf-152-58-60-9.ngrok-free.app/api/twilio/handle-call');
                break;
        }

        return response($response, 200)->header('Content-Type', 'text/xml');
    }

    public function handleCallFlow(Request $request)
    {
        $lang = $request->query('lang');
        $response = new VoiceResponse();

        if ($lang == 'en') {
            $response->say($this->botMessages['main-menu']['en'], ['voice' => 'alice','language' => 'en-IN']);
            $response->gather(['numDigits' => 1, 'action' => 'https://2fdf-152-58-60-9.ngrok-free.app/api/twilio/handle-response?lang=en', ['lang' => 'en']]);
        } else {
            $response->say($this->botMessages['main-menu']['heb'], ['voice' => 'alice', 'language' => 'he-IL']);
            $response->gather(['numDigits' => 1, 'action' => 'https://2fdf-152-58-60-9.ngrok-free.app/api/twilio/handle-response', ['lang' => 'heb']]);
        }

        return response($response, 200)->header('Content-Type', 'text/xml');
    }

    protected $keyMessage = [
        '1' => [          
               'en' => 'Broom Service - Room service for your home.
                    Broom Service is a professional cleaning company that offers high-quality cleaning services for homes or apartments, on a regular or one-time basis, without any unnecessary hassle.
                    We offer a variety of customized cleaning packages, from regular cleaning packages to additional services such as post-construction cleaning or pre-move cleaning, window cleaning at any height, and more.
                    You can find all of our services and packages on our website at  www.broomservice.co.il.
                    Our prices are fixed per visit, based on the selected package, and they include all the necessary services, including social benefits and travel.
                    We work with a permanent and skilled team of employees supervised by a work manager.
                    Payment is made by  credit card at the end of the month or after the visit, depending on the route chosen.
                    To receive a quote, you must schedule an appointment at your property with one of our supervisors, at no cost or obligation on your part, during which we will help you choose a package and then we will send you a detailed quote according to the requested work.
                    Please note that office hours are  Monday-Thursday from 8:00 to 14:00.
                    To schedule an appointment for a quote press 3 or  5 to speak with a representative.',
                'he' => 'ברום סרוויס - שירות חדרים לבית שלכם בית.
                    ברום סרוויס היא חברת ניקיון מקצועית המציעה שירותי ניקיון ברמה גבוהה לבית או לדירה, על בסיס קבוע או חד פעמי, ללא כל התעסקות מיותרת .
                    אנו מציעים מגוון חבילות ניקיון מותאמות אישית, החל מחבילות ניקיון על בסיס קבוע ועד לשירותים נוספים כגון, ניקיון לאחר שיפוץ או לפני מעבר דירה, ניקוי חלונות בכל גובה ועוד 
                    את כלל השירותים והחבילות שלנו תוכלו לראות באתר האינטרנט שלנו בכתובת  www.broomservice.co.il
                    המחירים שלנו קבועים לביקור, בהתאם לחבילה הנבחרת, והם כוללים את כל השירותים הנדרשים, לרבות תנאים סוציאליים ונסיעות . 
                    אנו עובדים עם צוות עובדים קבוע ומיומן המפוקח על ידי מנהל עבודה .
                    התשלום מתבצע בכרטיס אשראי בסוף החודש או לאחר הביקור, בהתאם למסלול שנבחר .
                    לקבלת הצעת מחיר, יש לתאם פגישה אצלכם בנכס עם אחד המפקחים שלנו, ללא כל עלות או התחייבות מצדכם שבמסגרתה נעזור לכם לבחור חבילה ולאחריה נשלח לכם הצעת מחיר מפורטת בהתאם לעבודה המבוקשת .
                    נציין כי שעות הפעילות במשרד הן בימים א-ה בשעות 8:00-14:00 .
                    לקביעת פגישה להצעת מחיר הקש 3 לשיחה עם נציג הקש  5.'
        ],
        '2' => [           
                'en' => 'We provide service in the following areas: 
                    - Tel Aviv
                    - Ramat Gan
                    - Givatayim
                    - Kiryat Ono
                    - Ganei Tikva
                    - Ramat HaSharon
                    - Kfar Shmaryahu
                    - Rishpon
                    - Herzliya
                    To schedule an appointment for a quote press 3 or 5 to speak with a representative.',

                'he' => 'אנו מספקים שירות באזור :
                    - תל אביב
                    - רמת גן
                    - גבעתיים
                    - קריית אונו
                    - גני תקווה
                    - רמת השרון
                    - כפר שמריהו
                    - רשפון
                    - הרצליה
                    לקביעת פגישה להצעת מחיר הקש 3 לשיחה עם נציג הקש  5.'
        ],
        '3' => [
                'en' => "To receive a quote, please send us messages with the following details Please send your full name",
                'he' => "כדי לקבל הצעת מחיר, אנא שלחו את הפרטים הבאים: שם מלא",            
        ],
        '4' => [    
                'en' => 'Existing customers can use our customer portal to get information, make changes to orders, and contact us on various matters.
                        You can also log in to our customer portal with the details you received at the time of registration at crm.broomservice.co.il.
                        Enter your phone number or email address with which you registered for the service ',
                'he' => 'לקוחות קיימים יכולים להשתמש בפורטל הלקוחות שלנו כדי לקבל מידע, לבצע שינויים בהזמנות וליצור איתנו קשר בנושאים שונים.
                        תוכלו גם להיכנס לפורטל הלקוחות שלנו עם הפרטים שקיבלתם במעמד ההרשמה בכתובת crm.broomservice.co.il.
                        הזן את מס הטלפון או כתובת המייל איתם נרשמת לשירות ',       
        ],
        '5' => [

                'en' => 'Dear customers, office hours are Monday-Thursday from 8:00 to 14:00.
                        If you contact us outside of business hours, a representative from our team will get back to you as soon as possible on the next business day, during business hours.
                        If you would like to speak to a human representative, please send a message with the word "Human Representative". ',
                'he' => 'לקוחות יקרים, שעות הפעילות במשרד הן בימים א-ה בשעות 8:00-14:00.
                        במידה ופניתם מעבר לשעות הפעילות נציג מטעמנו יחזור אליכם בהקדם ביום העסקים הבא, בשעות הפעילות.
                        אם אתם מעוניינים לדבר עם נציג אנושי, אנא שלחו הודעה עם המילה "נציג אנושי". ',
        ]                
    ];



    public function handleResponse(Request $request)
    {
        $lang = $request->query('lang');
        $digit = $request->input('Digits');
        $response = new VoiceResponse();

        if ($lang == 'en') {
            switch ($digit) {
                case '1':
                    $response->say($this->keyMessage['1']['en'], ['voice' => 'alice']);
                    break;
                case '2':
                    $response->say($this->keyMessage['2']['en'], ['voice' => 'alice']);
                    break;
                case '3':
                    $response->say($this->keyMessage['3']['en'], ['voice' => 'alice']);
                    break;
                case '4':
                    $response->say($this->keyMessage['4']['en'], ['voice' => 'alice']);
                    break; 
                case '5':
                    $response->say($this->keyMessage['5']['en'], ['voice' => 'alice']);
                    break;   
                default:
                    $response->say("Invalid input. Please try again.", ['voice' => 'alice']);
                    $response->redirect(route('twilio.handleCallFlow', ['lang' => 'en']));
                    break;
            }
        } else {
            switch ($digit) {
                case '1':
                    $response->say($this->keyMessage['1']['he'], ['voice' => 'alice', 'language' => 'he-IL']);
                    break;
                case '2':
                    $response->say($this->keyMessage['2']['he'], ['voice' => 'alice', 'language' => 'he-IL']);
                    break;
                case '3':
                    $response->say($this->keyMessage['3']['he'], ['voice' => 'alice', 'language' => 'he-IL']);
                    break;
                case '4':
                    $response->say($this->keyMessage['4']['he'], ['voice' => 'alice', 'language' => 'he-IL']);
                    break;
                case '5':
                    $response->say($this->keyMessage['5']['he'], ['voice' => 'alice', 'language' => 'he-IL']);
                    break;
                default:
                    $response->say("קלט לא חוקי. אנא נסה שוב.", ['voice' => 'alice', 'language' => 'he-IL']);
                    $response->redirect(route('twilio.handleCallFlow', ['lang' => 'heb']));
                    break;
            }
        }

        return response($response, 200)->header('Content-Type', 'text/xml');
    }



}