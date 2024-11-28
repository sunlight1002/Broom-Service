<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Offer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NotifyNonClientsOrHisNoPriceOffer extends Command
{
    protected $signature = 'notify:non-clients';
    protected $description = 'Notify non-clients or clients with no price offer or unsigned contracts';
    protected $whapiApiEndpoint;
    protected $whapiApiToken;

    public function __construct()
    {
        parent::__construct();
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    public function handle()
    {
        $clientNumbers = [
            '1234567890',
            '917665655655',
            '943433434343343',
            '565665555565665'
        ];

        $text = ['בוקר טוב, מה שלומך?

שמתי לב שעדיין לא התקדמתם עם הצעת המחיר שנשלחה אליכם מאיתנו.
לגמרי מובן שלפעמים צריך עוד זמן לחשוב או תמריץ קטן כדי לקבל החלטה שתשנה את החיים שלכם. ואני מבטיחה לך – זו לא קלישאה, אלא המציאות של מאות לקוחות מרוצים שמקבלים מאיתנו שירות קבוע כבר שנים רבות.

לקוחותינו כבר קיבלו את ההחלטה ששדרגה את איכות החיים שלהם, שחררה אותם מההתעסקות בניקיון הבית, ופינתה להם זמן אמיתי למה שחשוב באמת.

לכן, אנו מזמינים אתכם לנצל הזדמנות חד-פעמית ולקבל את שירות הניקיון שחיכיתם לו ברמה הגבוהה ביותר:
🔹 ביקור ראשון ללא מע"מ – כך שתוכלו להתרשם בעצמכם מהמקצועיות, האיכות והתוצאה שתשדרג לכם את הבית ואת איכות החיים.
🔹 ללא התעסקות, ללא התחייבות וללא דאגות – רק בית נקי ומזמין!

זו ההזדמנות שלכם להבין בדיוק מה אתם מקבלים בתמורה לכסף שלכם – ולמה מאות לקוחות מרוצים כבר בחרו בנו ועובדים איתנו שנים רבות.

מצרפת כאן לעיונכם המלצות מלקוחות קיימים שלנו כדי שתוכלו להתרשם בעצמכם מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

שימו לב – ההצעה תקפה לזמן מוגבל בלבד!

לפרטים נוספים או להזמנת ביקור ראשון, אתם מוזמנים להשיב להודעה זו או ליצור קשר ישירות איתי.
אשמח לעמוד לשירותכם בכל שאלה.

בברכה,
מורן
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il',
'בוקר טוב, מה שלומך?

ראיתי שפנית אלינו בעבר ולא התקדמת לפגישה או קבלת הצעת מחיר, ורציתי להזכיר שאנחנו כאן עבורך – תמיד ובכל עת שתצטרך.

מאות לקוחות שבחרו בנו כבר גילו איך שירותי הניקיון שלנו שדרגו את הבית שלהם ואת איכות החיים, תוך שהם משאירים את כל הדאגות מאחור.

מצרפת כאן לעיונך המלצות מלקוחות קיימים שלנו כדי שתוכלו להתרשם בעצמכם מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

אנחנו מזמינים אותך להצטרף אליהם וליהנות משירות מקצועי, אישי ואיכותי שמבטיח לך שקט נפשי ותוצאה מושלמת בכל פעם.

נשמח לעמוד לשירותך ולענות על כל שאלה או צורך – כל שעליך לעשות הוא לשלוח לנו הודעה, ואנחנו נדאג לכל היתר.


בברכה,
מורן
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il'];

    foreach ($clientNumbers as $number) {
        $client = Client::where('phone', 'like', '%' . $number)->first();

        if (!$client) {
            // 1. if client is not on the system or in the system but don't have a price offer sent then you send 2nd msg
            Log::info("Client does not exist in the system: $number");
            $this->sendMessage($number, $text[1]);
            continue;
        }

        $leadStatus = $client->lead_status; 
        
        // 1. if client is not on the system or in the system but don't have a price offer sent then you send 2nd msg
        $offerExists = $client->offers()
            ->exists();

        if (!$offerExists) {
            Log::info("No price offer exists for client: $number");
            $this->sendMessage($number, $text[1]);
            continue;
        }

        // 2. if client on the system and have a price offer but no contract or unsigned contract you send 1st msg
        $hasNoContracts = !$client->contract()->exists();
        $hasUnsignedContracts = $client->contract()->where('status', 'not-signed')->exists();

        if ($hasNoContracts || $hasUnsignedContracts) {
            Log::info("No contract or unsigned contract for client: $number");
            $this->sendMessage($number, $text[0]);
            continue;
        }

        // 3. for all pending clients from 17th of November to 1st of September you also send 2nd msg
        if ($leadStatus && $leadStatus->lead_status == 'pending' && 
            $client->created_at->between(Carbon::parse('2023-11-17'), Carbon::parse('2024-09-01'))) {
            Log::info("Pending client for client: $number");
            $this->sendMessage($number, $text[1]);
            continue;
        }

        // 4. for all potential clients/unverified contracts/unsinged contracts/declined contracts or price offers from 10th November to 1st September you send 1st msg
        // $isPotentialClient = $leadStatus && $leadStatus->lead_status == 'potential client' && 
        //                     $client->created_at->between(Carbon::parse('2023-11-10'), Carbon::parse('2024-09-01'));
        
        $unverifiedContracts = $client->contracts()
            ->whereIn('status', ['un-verified', 'not-signed', 'declined'])
            ->whereBetween('created_at', [Carbon::parse('2023-11-10'), Carbon::parse('2024-09-01')])
            ->exists();

        $priceOffers = $client->offers()
            ->where('status', 'accepted')
            ->whereBetween('created_at', [Carbon::parse('2023-11-10'), Carbon::parse('2024-09-01')])
            ->exists();

        if ( $unverifiedContracts && $priceOffers) {
            Log::info("Potential client, unverified contract, unsigned contract, declined contract, or price offer for client: $number");
            $this->sendMessage($number, $text[0]);
        }
    }

        $this->info('Notifications sent successfully.');
        return 0;
    }
    private function sendMessage($number, $message)
    {
        Log::info("Sending message to $number: $message");

        // $response = Http::withToken($this->whapiApiToken)
        //     ->post($this->whapiApiEndpoint . 'messages/text', [
        //         'to' => $number,
        //         'body' => $message
        //     ]);

        // Log::info($response->json());
    }

}
