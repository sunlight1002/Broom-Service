<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Twilio\TwiML\VoiceResponse;

class TwimlController extends Controller
{
    // Set the default language (static)
    
    protected $lang = 'en'; // Change to 'he' for Hebrew

    public function index()
    {
        
        $response = new VoiceResponse();
    
        // Initial message based on the static language
        $message = $this->getInitialMessage();
        $response->say($message,$this->getLocale());
    
        // Gather input for the user's selection
        $gather = $response->gather([
            'numDigits' => 1,
            'action' => url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml/handlelanguage'),
            'timeout' => 10
        ]);
    
        $menuPrompt = $this->lang === 'he'
        ? 'לחץ 1 למידע על השירות, 2 לאזורי השירות, 3 לקביעת פגישה להצעת מחיר, 4 לשירות לקוחות, 5 למעבר לנציג אנושי, או 7 לתפריט באנגלית.'
        : 'Press 1 for About the Service, 2 for Service Areas, 3 to set an appointment for a quote, 4 for Customer Service, 5 to switch to a human representative, or 7 for Hebrew Menu.';


        $gather->say($menuPrompt, $this->getLocale());
    
        return response($response)->header('Content-Type', 'application/xml');
    }


    public function handleLanguage(Request $request)
    {
        $digits = $request->input('Digits');
        $response = new VoiceResponse();
    
        // Response text based on user selection and static language
        switch ($digits) {
            case '1':
                $response->say($this->getServiceInfo(), $this->getLocale());
                $gather = $response->gather([
                    'numDigits' => 1,
                    'action' => url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml/handleSelection'), // Ensure this is your correct URL
                    'timeout' => 10
                ]);
                $menuPrompt = $this->lang === 'he'
                            ? 'לחץ 3 לתיאום פגישה להצעת מחיר או 5 לשיחה עם נציג.'
                            : 'Press 3 to schedule an appointment for a quote or 5 to speak with a representative.';
            
                $gather->say($menuPrompt, $this->getLocale());

                break;
            case '2':
                $response->say($this->getServiceAreas(), $this->getLocale());
                $gather = $response->gather([
                    'numDigits' => 1,
                    'action' => url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml/handleSelection'), // Ensure this is your correct URL
                    'timeout' => 10
                ]);
                $menuPrompt = $this->lang === 'he'
                            ? 'לחץ 3 לתיאום פגישה להצעת מחיר או 5 לשיחה עם נציג.'
                            : 'Press 3 to schedule an appointment for a quote or 5 to speak with a representative.';
            
                $gather->say($menuPrompt, $this->getLocale());
                break;
            case '3':

                $response->say($this->getAppointmentInfo(), $this->getLocale());
                $gather = $response->gather([
                    'input' => 'speech',
                    'action' => url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml/handleName'), // URL to handle the speech input
                    'timeout' => 10,
                    'hints' => 'name',
                    'speechTimeout' => 'auto', // Allow for automatic timeout
                ]);

                $menuPrompt = $this->lang === 'he' ? 'בבקשה אמור את שמך המלא אחרי הצפצוף.' : 'Please say your full name after the beep.';
                $gather->say($menuPrompt, $this->getLocale());
                
                break;
            case '4':
                $response->say($this->getCustomerServiceInfo(), $this->getLocale());
                break;
            case '5':
                $response->say($this->getHumanRepresentativeInfo(),  $this->getLocale());
                break;
            case '7':
                $response->say($this->getLangMenu(), $this->getLocale());
                break;
            case '9':
                $response->redirect(url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml'));
                break;
            case '0':
                $response->redirect(url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml'));
                break;
            default:

                $menuPrompt = $this->lang === 'he'
                ? 'בבקשה, לא הבנתי את הבחירה שלך.' : 'Sorry, I did not understand that choice.';
     
                $response->say($menuPrompt, $this->getLocale());
                $response->redirect(url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml'));
                break;
        }
    
        return response($response)->header('Content-Type', 'application/xml');
    }

    public function handleSelection(Request $request)
    {
        $digits = $request->input('Digits');
        $response = new VoiceResponse();
    
        switch ($digits) {
            case '3':
                $menuPrompt = $this->lang === 'he'
                ? 'בחרת לתאם פגישה להצעת מחיר. אנא המתן בעוד אנו מחברים אותך לנציג.'
                : 'You have chosen to schedule an appointment for a quote. Please wait while we connect you to a representative.';
            
                $response->say($menuPrompt, $this->getLocale());
            
                // Redirect or forward to the appropriate action for scheduling an appointment
                break;
            case '5':
                    $menuPrompt = $this->lang === 'he'
                    ? 'אתה מחובר עכשיו לנציג. נא להחזיק.'
                    : 'You will now be connected to a representative. Please hold.';
            
                    $response->say($menuPrompt, $this->getLocale());
            
                // Redirect or forward to the appropriate action for speaking with a representative
                break;
            default:
                    $menuPrompt = $this->lang === 'he'
                        ? 'סליחה, לא הבנתי את הבחירה שלך. אנא הקש 3 לתיאום פגישה או 5 לשיחה עם נציג.'
                        : 'Sorry, I did not understand that choice. Please press 3 to schedule an appointment or 5 to speak with a representative.';

                    $response->say($menuPrompt, $this->getLocale());

                $response->redirect(url('https://dc22-2405-201-2022-1089-7a1c-f46c-b00f-9d7f.ngrok-free.app/api/twiml'));
                break;
        }
    
        return response($response)->header('Content-Type', 'application/xml');
    }


    // Helper methods to get messages based on language
    protected function getInitialMessage()
    {
        $messages = [
            'en' => "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? At any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.",
            'he' => "היי, אני בר, הנציגה הדיגיטלית של ברום סרוויס. איך אוכל לעזור לך היום? \n\nבכל שלב תוכלו לחזור לתפריט הראשי ע\"י שליחת המספר 9 או לחזור תפריט אחד אחורה ע\"('י') שליחת הספרה 0\n\n",
        ];
        return $messages[$this->lang] ?? $messages['en'];
    }

    protected function getLocale()
    {
        return $this->lang === 'he' ? ['language' => 'he-IL', 'voice' => 'Google.he-IL-Standard-A'] : ['language' => 'en-US', 'voice' => 'Polly.Joanna'];
    }

    // Define the service info messages for the selected language
    protected function getServiceInfo()
    {
    
        $messages = [
            'en' => "Broom Service - Room service for your home. Broom Service is a professional cleaning company that offers high-quality cleaning services for homes or apartments, on a regular or one-time basis, without any unnecessary hassle.",
            'he' => "ברום סרוויס - שירות חדרים לבית שלך. ברום סרוויס היא חברת ניקיון מקצועית המציעה שירותי ניקיון ברמה גבוהה לבתים או דירות, על בסיס קבוע או חד פעמי, ללא כל התעסקות מיותרת."
        ];
    
        return $messages[$this->lang] ?? $messages['en'];

    }

    protected function getServiceAreas()
    {
        $messages = [
            'en' => "We provide service in the following areas:,
                    - Tel Aviv,
                    - Ramat Gan,
                    - Givatayim,
                    - Kiryat Ono,
                    - Ganei Tikva,
                    - Ramat HaSharon,
                    - Kfar Shmaryahu,
                    - Rishpon,
                    - Herzliya,",
            'he' => "אנו מספקים שירותים באזורים שונים. אנא בדוק את האתר שלנו למידע נוסף.",
        ];
        return $messages[$this->lang] ?? $messages['en'];
    }

    protected function getAppointmentInfo()
    {
        $messages = [
            'en' => "To receive a quote, please send us messages with the following details\n\nPlease send your full name",
            'he' => "כדי לקבל הצעת מחיר, אנא שלחו את הפרטים הבאים: 📝\n\nשם מלא",
        ];
        return $messages[$this->lang] ?? $messages['en'];
    }

    protected function getCustomerServiceInfo()
    {
        $messages = [
            'en' => 'Existing customers can use our customer portal to get information, make changes to orders, and contact us on various matters.
                    You can also log in to our customer portal with the details you received at the time of registration at crm.broomservice.co.il.
                    Enter your phone number or email address with which you registered for the service 📝',
            'he' => 'לקוחות קיימים יכולים להשתמש בפורטל הלקוחות שלנו כדי לקבל מידע, לבצע שינויים בהזמנות וליצור איתנו קשר בנושאים שונים.
                    תוכלו גם להיכנס לפורטל הלקוחות שלנו עם הפרטים שקיבלתם במעמד ההרשמה בכתובת crm.broomservice.co.il.
                    הזן את מס הטלפון או כתובת המייל איתם נרשמת לשירות 📝',
        ];
        return $messages[$this->lang] ?? $messages['en'];
    }

    protected function getHumanRepresentativeInfo()
    {
        $messages = [
            'en' => 'Dear customers, office hours are Monday-Thursday from 8:00 to 14:00.
                    If you contact us outside of business hours, a representative from our team will get back to you as soon as possible on the next business day, during business hours.
                    If you would like to speak to a human representative, please send a message with the word "Human Representative". 🙋🏻',
            'he' => 'לקוחות יקרים, שעות הפעילות במשרד הן בימים א-ה בשעות 8:00-14:00.
                    במידה ופניתם מעבר לשעות הפעילות נציג מטעמנו יחזור אליכם בהקדם ביום העסקים הבא, בשעות הפעילות.
                    אם אתם מעוניינים לדבר עם נציג אנושי, אנא שלחו הודעה עם המילה "נציג אנושי". 🙋🏻',
        ];
        return $messages[$this->lang] ?? $messages['en'];
    }

    protected function getLangMenu()
    {
        return "To hear this menu in English, press 1.";
    }



    public function handleName(Request $request)
    {
        $response = new VoiceResponse();
        $speechResult = $request->input('SpeechResult'); // The recognized speech

        // Log the name
        // You can replace this with your preferred logging or storage method
        \Log::info('User provided name: ' . $speechResult);

        // Acknowledge receipt of the name and end the call or proceed as needed
        $response->say('Thank you! We have received your name. We will contact you shortly.');

        // You might want to end the call or redirect to another endpoint
        $response->hangup(); // End the call

        return response($response)->header('Content-Type', 'application/xml');
    }


}
