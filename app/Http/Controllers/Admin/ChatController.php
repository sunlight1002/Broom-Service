<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\TextResponse;
use App\Models\WebhookResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function chats()
    {
        $data = WebhookResponse::distinct()->where('number', '!=', null)->get(['number']);

        $clients = [];

        if (count($data) > 0) {
            foreach ($data as $k => $_no) {
                $no = $_no->number;
                $_unreads = WebhookResponse::where(['number' => $no, 'read' => 0])->pluck('read');

                $data[$k]['unread'] = count($_unreads);

                if (strlen($no) > 10) {
                    $cl = Client::where('phone', 'like', '%' . substr($no, 2) . '%')->first();
                } else {
                    $cl = Client::where('phone', 'like', '%' . $no . '%')->first();
                }

                if (!is_null($cl)) {
                    $clients[] = [
                        'name' => $cl->firstname . " " . $cl->lastname,
                        'id'   => $cl->id,
                        'num'  => $no,
                        'client' => ($cl->status == 0) ? 0 : 1
                    ];
                }
            }
        }

        return response()->json([
            'data' => $data,
            'clients' => $clients,
        ]);
    }

    public function chatsMessages($no)
    {
        $chat = WebhookResponse::where('number', $no)->get();

        WebhookResponse::where(['number' => $no, 'read' => 0])->update([
            'read' => 1
        ]);

        $lastMsg = WebhookResponse::where('number', $no)->get()->last();

        ($lastMsg && $lastMsg->created_at < Carbon::now()->subHours(24)->toDateTimeString())
            ?
            $expired = 1
            : $expired = 0;

        return response()->json([
            'chat' => $chat,
            'expired' => $expired
        ]);
    }

    public function chatReply(Request $request)
    {
        $result = sendWhatsappMessage($request->number, '', array('message' => $request->message));

        $response = WebhookResponse::create([
            'status'        => 1,
            'name'          => 'whatsapp',
            'message'       => $request->message,
            'number'        => $request->number,
            'read'          => !is_null(Auth::guard('admin')) ? 1 : 0,
            'flex'          => !is_null(Auth::guard('admin')) ? 'A' : 'C',
        ]);

        return response()->json([
            'msg' => 'message send successfully'
        ]);
    }

    public function saveResponse(Request $request)
    {
        TextResponse::truncate();
        $responses = $request->data;

        if (count($responses) > 0) {
            foreach ($responses as $k => $res) {

                TextResponse::create(
                    [
                        'keyword' => $res['keyword'],
                        'heb'     => $res['heb'],
                        'eng'     => $res['eng'],
                        'status'  => $res['status']
                    ]
                );
            }
        }

        return response()->json([
            'message' => 'Responses saved successfully'
        ]);
    }

    public function chatResponses()
    {
        $responses = TextResponse::all();

        return response()->json([
            'responses' => $responses
        ]);
    }

    public function chatRestart(Request $request)
    {
        sendWhatsappMessage($request->number, $request->template, array('name' => ''));
        $client = Client::where('phone', 'like', '%' . $request->number . '%')->first();
        $_msg = TextResponse::where('status', '1')->where('keyword', 'main_menu')->first();

        WebhookResponse::create([
            'status'        => 1,
            'name'          => 'whatsapp',
            'entry_id'      => '',
            'message'       => $_msg ? ($client && $client->lng == 'en') ? $_msg->eng : $_msg->heb: '',
            'number'        => $request->number,
            'flex'          => 'A',
            'read'          => 1,
            'data'          => '',
        ]);

        return response()->json([
            'message' => 'chat restarted'
        ]);
    }

    public function chatSearch($s, $type)
    {
        if ($type == 'number')
            $data = WebhookResponse::distinct()->where('number', 'like', '%' . $s . '%')->get(['number']);
        else
            $data = WebhookResponse::distinct()
                ->Where(function ($query) use ($s) {
                    for ($i = 0; $i < count($s); $i++) {
                        $r = str_replace('+', '', $s[$i]);
                        $query->orwhere('number', 'like',  '%' . $r . '%');
                    }
                })->get(['number']);

        $clients = [];

        if (count($data) > 0) {
            foreach ($data as $k => $_no) {
                $no = $_no->number;
                $_unreads = WebhookResponse::where(['number' => $no, 'read' => 0])->pluck('read');

                $data[$k]['unread'] = count($_unreads);

                if (strlen($no) > 10) {
                    $cl = Client::where('phone', 'like', '%' . substr($no, 2) . '%')->first();
                } else {
                    $cl = Client::where('phone', 'like', '%' . $no . '%')->first();
                }

                if (!is_null($cl)) {
                    $clients[] = [
                        'name' => $cl->firstname . " " . $cl->lastname,
                        'id'   => $cl->id,
                        'num'  => $no,
                        'client' => ($cl->status == 0) ? 0 : 1
                    ];
                }
            }

            return response()->json([
                'data' => $data,
                'clients' => $clients
            ]);
        }
    }

    public function search(Request $request)
    {
        $s = $request->s;

        if (is_null($s)) {
            return $this->chats();
        }

        if (is_numeric($s)) {

            return $this->chatSearch($s, 'number');
        } else {

            $cx = explode(' ', $s);
            $fn  = $cx[0];
            $ln  = isset($cx[1]) ? $cx[1] : $cx[0];
            $clients = Client::where('firstname', 'like', '%' . $fn . '%')->orwhere('lastname', 'like', '%' . $ln . '%')->get('phone');

            if (count($clients) > 0) {
                $nos = [];
                foreach ($clients as $client) {
                    $nos[] = $client->phone;
                }

                return $this->chatSearch($nos, 'name');
            }
        }
    }

    public function responseImport()
    {
        TextResponse::truncate();

        TextResponse::create(
            [
                'keyword' => 'main_menu',
                'heb'     => "אז מי אנחנו?\nברוום סרוויס הינה חברת ניקיון פרימיום הפועלת משנת 2015 ומספקת מענה לאנשים המחפשים שירותי ניקיון ברמה גבוהה לבית או הדירה וללא כל התעסקות מיותרת.\n\nבשונה מהאלטרנטיבות שאתם מכירים, כמו עוזרת בית או חברות שיתווכו בינכם לבין עוזרת לפי שעה או כח אדם לפי שעה,\nאצלנו המחיר הוא מחיר קבוע לביקור ומתומחר לפי 5 חבילות ברמות שונות המותאמות לכם ולצרכים שלכם.\n\nאנו מציעים גם שירותי ניקיון כללי ויסודי וגם שירותי סידור וארגון ארונות עב קבוע או חד פעמי.
                ככל שעולים ברמת החבילה, אתם מקבלים יותר שירותים (בהתאם לצרכים שלכם) והמחיר נקבע בהתאם לעבודה ולאחר פגישה אצלכם בבית.\n\nכדי לקבל הצעת מחיר על השירות, יש לתאם פגישה להצעת מחיר בנכס שתרצו שננקה.
                הפגישה ללא עלות או כל התחייבות מצדכם ולוקחת באיזור 10-15דק.
                לאחר הפגישה, אנו שולחים הצעת מחיר מסודרת ומפורטת, בהתאם לשירות או החבילה המתאימה לכם,\n \nכשהמחיר הוא לביקור ומגלם בתוכו את הכל, תנאים סוציאליים, נסיעות, עובדים קבועים, בימים קבועים (למי שלוקח פעם בשבוע או יותר- אחרת אין התחייבות)  המגיעים עם כל החומרים והציוד לעבודה (למעט שואב דלי ומגב שאת זה הלקוח מספק) ומפוקחים עי מנהל עבודה מטעמנו, שיוודא כי העבודה תמיד לשביעות רצונכם ובסטנדרטים שלנו.
                התשלום מתבצע בסוף החודש או לאחר הביקור- במידה ומדובר בביקור חד פעמי.\n\nכשהמחיר הוא לביקור ומגלם בתוכו את הכל, תנאים סוציאליים, נסיעות, עובדים קבועים, בימים קבועים (למי שלוקח פעם בשבוע או יותר- אחרת אין התחייבות)  המגיעים עם כל החומרים והציוד לעבודה (למעט שואב דלי ומגב שאת זה הלקוח מספק) ומפוקחים עי מנהל עבודה מטעמנו, שיוודא כי העבודה תמיד לשביעות רצונכם ובסטנדרטים שלנו.
                התשלום מתבצע בסוף החודש או לאחר הביקור- במידה ומדובר בביקור חד פעמי.\nהתשלום בכרטיס אשראי, כנגד חשבונית- מחיר לביקור כפול מספר הביקורים (בתוספת שירותים נוספים שאולי הזמנתם  באותו חודש כמו שירותי אירוח, חלונות, פוליש, סידור ארונות וכו)
                ברום סרוויס היא אחת מחברות הניקיון היחידות שקיבלו רישיון ממשרד הכלכלה. כל עובדי החברה מקבלים תשלום גבוה מהיום הראשון בעבודה, ימי חופש ומחלה, מקבלים הפרשות לפנסיה ולקרן השתלמות כחוק. ",
                'eng'     => "Hi, I'm Bar, the digital representative of Broom Service. How can I help you today? 😊\n\nAt any stage, you can return to the main menu by sending the number 9 or return one menu back by sending the number 0.\n\n1. About the Service\n\n2. Service Areas\n\n3. Set an appointment for a quote\n\n4. Customer Service\n\n5. Switch to a human representative (during business hours)",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => '1',
                'heb'     => "אנחנו מספקים שירות בתל אביב, רמת גן, גבעתיים, קריית אונו, רמת השרון, כפר שמריהו והרצליה. \n\nהאם תרצו לתאם פישה להצאת מחיר?",
                'eng'     => "Broom Service - Room service for your 🏠.\n\nBroom Service is a professional cleaning company that offers ✨ high-quality cleaning services for homes or apartments, on a regular or one-time basis, without any unnecessary 🤯 hassle.\n\nWe offer a variety of 🧹 customized cleaning packages, from regular cleaning packages to additional services such as post-construction cleaning or pre-move cleaning, window cleaning at any height, and more.\n\nYou can find all of our services and packages on our website at 🌐 www.broomservice.co.il.\n\nOur prices are fixed per visit, based on the selected package, and they include all the necessary services, including ☕️ social benefits and travel.\n\nWe work with a permanent and skilled team of employees supervised by a work manager.\n\nPayment is made by 💳 credit card at the end of the month or after the visit, depending on the route chosen.\n\nTo receive a quote, you must schedule an appointment at your property with one of our supervisors, at no cost or obligation on your part, during which we will help you choose a package and then we will send you a detailed quote according to the requested work.\n\nPlease note that office hours are 🕖 Monday-Thursday from 8:00 to 14:00.\n\nTo schedule an appointment for a quote or speak with a representative, press ☎️ 3.",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => '2',
                'heb'     => "איך מתחילים?\nלפני השירות מגיע אחד המפקחים של החברה, לנכס שלכם, לפגישה ללא עלות וללא התחייבות.\nהמפקח, בוחן מהם הצרכים שלכם, בודק אילו משטחים יש לנקות בנכס ומאיזה חומר הם עשויים על מנת להתאים להם את חומר הניקוי הטוב ביותר,
                רואה את גודל הנכס, מספר חדרי שירותים וחדרי שינה ובהתאם לכך מתאים לכם את החבילה והעובד המתאים.\nלאחר הפגישה תשלח אליכם הצעת מחיר אותה תוכלו לאשר ולהזמין את השירות- כשבוע מראש, או ע\"ב מקום פנוי באותו השבוע.\nנציג אנושי יצור איתך קשר בהקדם\nלקבוע פגישה",
                'eng'     => "We provide service in the following areas: 🗺️\n\n• Tel Aviv\n• Ramat Gan\n• Givatayim\n• Kiryat Ono\n• Ramat HaSharon\n• Kfar Shmaryahu\n• Herzliya\nTo schedule an appointment for a quote or speak with a representative, press ☎️ 3.",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => '3',
                'heb'     => "היי, כיף לראות אותך שוב \n\n1. יצירת קשר עם מנהל עבודה \n2. הנהלת חשבונות \n3. ביטול שירות\n4.מעבר לנציג אנושי (בשעות הפעילות)",
                'eng'     => "To receive a quote, please send us a message with the following details: 📝\n\n• Full name\n• Phone number\n• Full address\n• Email adress\n\nA representative from our team will contact you shortly to schedule an appointment.\n\nIs there anything else I can help you with today? 👋",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => '4',
                'heb'     => "תודה רבה על תגובתך, מנהל העבודה יצור איתך קשר בהקדם",
                'eng'     => "Existing customers can use our customer portal to get information, make changes to orders, and contact us on various matters.\n\nYou can also log in to our customer portal with the details you received at the time of registration at crm.broomservice.co.il.\n\nEnter your phone number or email address with which you registered for the service 📝",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => '4_existing_customers_service_menu',
                'heb'     => "תודה רבה על תגובתך, נציג מהנהלת חשבונות יצור איתך קשר בהקדם",
                'eng'     => "1. View your quotes\n\n2. View your contracts\n\n3. When is my next service?\n\n4. Cancel a one-time service\n\n5. Terminate the agreement\n\n6. Contact a representative",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => '4_4',
                'heb'     => "תודה רבה על תגובתך, נציג אנושי יצור איתך קשר בהקדם",
                'eng'     => "Dear customer, according to the terms of service, cancellation of the service may be subject to cancellation fees. Are you sure you want to cancel the service?",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => '4_6',
                'heb'     => "אנא הישאר זמין, נציג אנושי יצור איתך קשר בהקדם",
                'eng'     => "Who would you like to speak to?\n\n1. Office manager and scheduling\n\n2. Customer service\n\n3. Accounting and billing",
                'status'  => '1'
            ]
        );


        TextResponse::create(
            [
                'keyword' => '5',
                'heb'     => "אנא הישאר זמין, נציג אנושי יצור איתך קשר בהקדם",
                'eng'     => "Dear customers, office hours are Monday-Thursday from 8:00 to 14:00.\n\nIf you contact us outside of business hours, a representative from our team will get back to you as soon as possible on the next business day, during business hours.\n\nIf you would like to speak to a human representative, please send a message with the word \"Human Representative\". 🙋🏻",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => 'representative_contact',
                'heb'     => "תודה רבה על תגובתך, נציג אנושי יצור איתך קשר בהקדם",
                'eng'     => "A representative from our team will contact you shortly.",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => 'anything_else',
                'heb'     => "תודה רבה על תגובתך, נציג אנושי יצור איתך קשר בהקדם",
                'eng'     => "Is there anything else I can help you with today? 👋",
                'status'  => '1'
            ]
        );

        TextResponse::create(
            [
                'keyword' => 'hope_helped',
                'heb'     => "תודה רבה על תגובתך, נציג אנושי יצור איתך קשר בהקדם",
                'eng'     => "I hope I helped! 🤗",
                'status'  => '1'
            ]
        );

        return response()->json(['message' => 'chat responses added']);
    }

    public function Participants()
    {
        $url = 'https://graph.facebook.com/v18.0/' . config('services.facebook.account_id') . '/conversations?fields=participants&limit=100000000000000000000000000000000000000000000000000000&access_token=' . config('services.facebook.access_token');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $_p = json_decode($result);

        return response()->json([
            'data' => $_p,
            'page_id' => config('services.facebook.account_id')
        ]);
    }

    public function messengerMessage($id)
    {
        $url = 'https://graph.facebook.com/v17.0/' . $id . '/?fields=participants,messages{id,message,created_time,from}&access_token=' . config('services.facebook.access_token');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $_p = json_decode($result);

        return response()->json([
            'chat' => $_p,
        ]);
    }

    public function messengerReply(Request $request)
    {
        /*$ch = curl_init();
        
        $url = 'https://graph.facebook.com/v18.0/'.config('services.facebook.account_id').'/messages?recipient={id:'.intval($request->pid).'}&message={text:"i am string"}&messaging_type=RESPONSE&access_token='.env("FB_USER_ACCESS_TOKEN");

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        dd($result);
        $resp = json_decode($result);
        */

        $accessToken = config('services.facebook.access_token');

        $url = "https://graph.facebook.com/v18.0/" . config('services.facebook.account_id') . "/messages";
        $messageText = strtolower($request->message);
        $senderId = config('services.facebook.account_id');
        $recipientId = $request->pid;
        $response = null;

        $response = ['recipient' => ['id' => $recipientId], 'sender' => ['id' => $senderId], 'message' => ['text' => $messageText], 'access_token' => $accessToken];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $result = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode($result);
        return response()->json([
            'data' => $resp
        ]);
    }

    public function deleteConversation(Request $request)
    {
        $chats = WebhookResponse::where('number', $request->number)->delete();
        if($chats){
            return response()->json([
                'msg' => 'Conversation has been deleted!'
            ]);
        }else{
            return response()->json([
                'msg' => 'No conversation found!'
            ], 422);
        }
    }
}
