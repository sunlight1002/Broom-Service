<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\TextResponse;
use App\Models\WebhookResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class ChatController extends Controller
{

    public function index(Request $request)
    {
        // Fetch all webhook responses from the database
        $webhookResponses = WebhookResponse::all();

        // Return a JSON response
        return response()->json($webhookResponses);
    }

    // public function chats()
    // {
    //     $data = WebhookResponse::distinct()->where('number', '!=', null)->get(['number']);

    //     $clients = [];

    //     if (count($data) > 0) {
    //         foreach ($data as $k => $_no) {
    //             $no = $_no->number;
    //             $_unreads = WebhookResponse::where(['number' => $no, 'read' => 0])->pluck('read');

    //             $data[$k]['unread'] = count($_unreads);

    //             if (strlen($no) > 10) {
    //                 $cl = Client::where('phone', 'like', '%' . substr($no, 2) . '%')->first();
    //             } else {
    //                 $cl = Client::where('phone', 'like', '%' . $no . '%')->first();
    //             }

    //             if (!is_null($cl)) {
    //                 $clients[] = [
    //                     'name' => $cl->firstname . " " . $cl->lastname,
    //                     'id'   => $cl->id,
    //                     'num'  => $no,
    //                     'client' => ($cl->status == 0) ? 0 : 1
    //                 ];
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'data' => $data,
    //         'clients' => $clients,
    //     ]);
    // }

    // public function chats(Request $request)
    // {
    //     $page = $request->input('page', 1);  // Get the page number from the request, default to 1 if not provided
    //     $from = $request->input('from', null);
    //     \Log::info($from);
    //     $perPage = 20;  // Number of records per page

    //     // Fetch paginated data
    //     $data = WebhookResponse::distinct()
    //         ->where('number', '!=', null)
    //         ->skip(($page - 1) * $perPage)  // Skip the records based on the current page
    //         ->take($perPage)  // Limit the results to the number of records per page
    //         ->get(['number']);

    //     $clients = [];

    //     if ($data->count() > 0) {
    //         foreach ($data as $k => $_no) {
    //             $no = $_no->number;
    //             $_unreads = WebhookResponse::where(['number' => $no, 'read' => 0])->pluck('read');

    //             $data[$k]['unread'] = count($_unreads);

    //             if (strlen($no) > 10) {
    //                 $cl = Client::where('phone', 'like', '%' . substr($no, 2) . '%')->first();
    //             } else {
    //                 $cl = Client::where('phone', 'like', '%' . $no . '%')->first();
    //             }

    //             if (!is_null($cl)) {
    //                 $clients[] = [
    //                     'name' => $cl->firstname . " " . $cl->lastname,
    //                     'id'   => $cl->id,
    //                     'num'  => $no,
    //                     'client' => ($cl->status == 0) ? 0 : 1
    //                 ];
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'data' => $data,
    //         'clients' => $clients,
    //     ]);
    // }

public function chats(Request $request)
{
    $page = $request->input('page', 1);
    $from = $request->input('from');
    $unread = $request->boolean('unread');
    $filter = $request->input('filter');
    $start_date = $request->input('start_date');
    $end_date = $request->input('end_date');
    $search = $request->input('search');
    $isChat = $request->boolean('isChat');

    $perPage = 20;

    $data = collect();
    $clients = [];

    $isGroupNumber = fn($number) => strpos($number, '@g.us') !== false;

    if (!$isChat) {
        $clientQuery = Client::with('lead_status');

        if ($filter) {
            $clientQuery->whereHas('lead_status', function ($q) use ($filter) {
                $q->where('lead_status', $filter);
            });
        }

        if ($search) {
            $searchTerm = '%' . $search . '%';

            if (is_numeric($search)) {
                $clientQuery->where('phone', 'like', $searchTerm);
            } else {
                $clientQuery->where(function ($q) use ($searchTerm) {
                    $q->where('firstname', 'like', $searchTerm)
                      ->orWhere('lastname', 'like', $searchTerm);
                });
            }
        }

        if(!is_null($start_date) && !is_null($end_date)){
            $clientQuery->whereBetween('created_at', [$start_date, $end_date]);
        }

        $clientsPaginated = $clientQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // foreach ($clientsPaginated as $client) {
        //     $number = $client->phone;

        //     if (!$number || $isGroupNumber($number)) continue;

        //     $unreadCount = WebhookResponse::where('number', $number)
        //         ->where('read', 0)
        //         ->count();

        //     $entry = new \stdClass();
        //     $entry->number = $number;
        //     $entry->unread = $unreadCount;
        //     $data->push($entry);

        //     $clients[] = [
        //         'name'   => trim("{$client->firstname} {$client->lastname}"),
        //         'id'     => $client->id,
        //         'num'    => $number,
        //         'client' => $client->status != 0 ? 1 : 0,
        //     ];
        // }

        return response()->json([
            // 'data'    => $data->values(),
            'clients' => $clientsPaginated,
        ]);
    }

   $query = WebhookResponse::query()
    ->select('webhook_responses.number')
    ->when($unread, function ($q) {
        $q->where('read', 0);
    })
    ->whereNotNull('webhook_responses.number')
    ->groupBy('webhook_responses.number')
    ->orderBy(DB::raw('MAX(webhook_responses.created_at)'), 'desc') // latest message per number
    ->leftJoin('clients', 'webhook_responses.number', '=', 'clients.phone'); // JOIN for advanced filtering

    // Filter by 'from'
    if ($from) {
        $query->where('webhook_responses.from', $from);
    }

    // Filter by date range
    if ($start_date && $end_date) {
        $query->whereBetween('webhook_responses.created_at', [
            Carbon::parse($start_date)->startOfDay(),
            Carbon::parse($end_date)->endOfDay()
        ]);
    }

    // Search condition
    if ($search) {
        $searchTerm = '%' . $search . '%';

        $query->where(function ($q) use ($search, $searchTerm) {
            if (is_numeric($search)) {
                // Numeric search: match phone, number, or message
                $q->orWhere('webhook_responses.number', 'like', $searchTerm)
                ->orWhere('webhook_responses.message', 'like', $searchTerm)
                ->orWhere('clients.phone', 'like', $searchTerm);
            } else {
                // Text search: match firstname, lastname, or message
                $q->orWhere('clients.firstname', 'like', $searchTerm)
                ->orWhere('clients.lastname', 'like', $searchTerm)
                ->orWhere('webhook_responses.message', 'like', $searchTerm);
            }
        });
    }


    $rawData = $query
        ->skip(($page - 1) * $perPage * 2)
        ->take($perPage * 2)
        ->get();

    foreach ($rawData as $entry) {
        $number = $entry->number;
        if ($isGroupNumber($number)) continue;

        $unreadCount = WebhookResponse::where('number', $number)->where('read', 0)->count();
        $client = Client::with('lead_status')->where('phone', $number)->first();

        if (!empty($filter)) {
            if (!$client || !$client->lead_status || $client->lead_status->lead_status !== $filter) {
                continue;
            }
        }

        $entry->unread = $unreadCount;
        $data->push($entry);

        if ($client) {
            $clients[] = [
                'name'   => trim("{$client->firstname} {$client->lastname}"),
                'id'     => $client->id,
                'num'    => $number,
                'client' => $client->status != 0 ? 1 : 0,
            ];
        }

        if ($data->count() >= $perPage) break;
    }

    return response()->json([
        'data'    => $data->values(),
        'clients' => $clients,
    ]);
}


public function personalChat(Request $request)
{
    $page = $request->input('page', 1);
    $from = $request->input('from');
    $unread = $request->boolean('unread'); // ensure it's treated as boolean
    $filter = $request->input('filter') ?? null;
    $start_date = $request->input('start_date');
    $end_date = $request->input('end_date');
    $perPage = 20;

    \Log::info("Filter: {$filter}, Unread only: " . ($unread ? 'yes' : 'no'));

    $query = WebhookResponse::query()
        ->select('number')
        ->groupBy('number')
        ->whereNotNull('number')
        ->orderBy('created_at', 'desc');

    if ($from) {
        $query->where('from', $from);
    }

    if ($start_date && $end_date) {
        $query->whereBetween('created_at', [
            Carbon::parse($start_date)->startOfDay(),
            Carbon::parse($end_date)->endOfDay()
        ]);
    }

    $rawData = $query
        ->skip(($page - 1) * $perPage * 2)
        ->take($perPage * 2)
        ->get();

    $data = collect();
    $clients = [];

    foreach ($rawData as $entry) {
        $number = $entry->number;

        // Skip group chats
        if (strpos($number, '@g.us') !== false) {
            continue;
        }

        // Load client with lead_status
        $client = Client::with('lead_status')->where('phone', $number)->first();

        // Count unread messages
        $unreadCount = WebhookResponse::where('number', $number)
            ->where('read', 0)
            ->count();

        // Apply unread filter if requested
        if ($unread && $unreadCount === 0) {
            continue;
        }

        // Apply lead_status filter if provided
        if (!empty($filter)) {
            if (!$client || !$client->lead_status || $client->lead_status->lead_status != $filter) {
                continue;
            }
        }

        $entry->unread = $unreadCount;
        $data->push($entry);

        if ($client) {
            $clients[] = [
                'name'   => trim("{$client->firstname} {$client->lastname}"),
                'id'     => $client->id,
                'num'    => $number,
                'client' => $client->status != 0 ? 1 : 0,
            ];
        }

        if ($data->count() >= $perPage) {
            break;
        }
    }

    return response()->json([
        'data'    => $data->values(),
        'clients' => $clients,
    ]);
}




    
    public function allLeads(Request $request)
    {
        $perPage = $request->get('per_page', 20); // default 20
        $filter = $request->get('filter');
    
        if (!empty($filter)) {
            $clients = Client::whereHas('lead_status', function($query) use ($filter) {
                $query->where('lead_status', $filter);
            })->paginate($perPage);
        }else{
            $clients = Client::paginate($perPage);
        }
    
        return response()->json($clients);
    }
    


    public function storeWebhookResponse(Request $request)
    {
        $response = $request->all();
        // Validate incoming request data
        $validatedData = $request->validate([
            'number' => 'required|string|unique:webhook_responses,number',
        ]);

        // Check if the number already exists in the WebhookResponse table
        $existingRecord = WebhookResponse::where('number', $validatedData['number'])->first();

        // If it exists, return a response indicating the record already exists
        if ($existingRecord) {
            return response()->json(['message' => 'Record with this number already exists.'], 409); // 409 Conflict
        }

        // Check if the number exists in the clients table
        $client = Client::where('phone', $validatedData['number'])->first();

        // If a client exists, use their name; otherwise, use a default name
        $name = $client ? $client->firstname . ' ' . $client->lastname : 'Default Name';

        // Create a new WebhookResponse record with default values
        $webhookResponse = WebhookResponse::create([
            'name' => $name,                      // Fill name from the client if exists
            'status' => 1,                 // Set your default status here
            'entry_id' => null,                      // Set your default entry_id here
            'message' => '',       // Set your default message here
            'number' => $validatedData['number'], // Use the validated number from the request
            'data' => json_encode([]),            // Set default data here (as JSON string)
            'flex' => 'C',                        // Set your default flex value here
            'read' => 0,                      // Set your default read status here
            'res_id' => null,        // Set your default res_id here
            'wa_id' => null           // Set your default wa_id here
        ]);

        return response()->json(['message' => 'Webhook response stored successfully.', 'data' => $webhookResponse], 201); // 201 Created
    }



    public function chatsMessages($no, Request $request)
    {
        $offical = false;
        $from = $request->input('from');
        \Log::info($from);
        $chat = WebhookResponse::where('number', $no)->where('from', $from)->get();

        WebhookResponse::where(['number' => $no, 'read' => 0])->update([
            'read' => 1
        ]);

        $lastMsg = WebhookResponse::where('number', $no)->get()->last();
        $expired = ($lastMsg && $lastMsg->created_at < Carbon::now()->subHours(24)) ? 1 : 0;

        if(in_array($from, [str_replace("whatsapp:+", "", config('services.twilio.twilio_whatsapp_number')), str_replace("whatsapp:+", "", config('services.twilio.worker_lead_whatsapp_number'))])) {
            $offical = true;
        }

        $client = Client::where('phone', $no)->first();

        $clientName = $client ? $client->firstname . " " . $client->lastname : 'Unknown';

        return response()->json([
            'chat' => $chat,
            'expired' => $expired,
            'offical' => $offical == true ? 1 : 0,
            'clientName' => $clientName,
            'isExist' => $client ? 1 : 0
        ]);
    }


    public function chatReply(Request $request)
    {
        $replyId = $request->input('replyId'); // Get replyId from request
        $from = $request->input('from');
        $mediaPath = null;
        $result = null;
        $mimeType = null;
        $offical = false;

        $twilioAccountSid = config('services.twilio.twilio_id');
        $twilioAuthToken = config('services.twilio.twilio_token');
        $twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');

        // Initialize the Twilio client
        $twilio = new TwilioClient($twilioAccountSid, $twilioAuthToken);

        if(in_array($from, [str_replace("whatsapp:+", "", config('services.twilio.twilio_whatsapp_number')), str_replace("whatsapp:+", "", config('services.twilio.worker_lead_whatsapp_number'))])) {
            $offical = true;
        }

        if($offical == true) {

            $twi = $twilio->messages->create(
                "whatsapp:+$request->number",
                [
                    "from" => "whatsapp:+$from",
                    "body" => $request->message,
                    // "statusCallback" => config("services.twilio.webhook") . "twilio/status-callback",

                ]
            );
            \Log::info("twilio response". $twi->sid);

        }else{
            // Check if a media file is included in the request
            if ($request->hasFile('media')) {
                // Handle media upload
                $mediaFile = $request->file('media');
                $mediaPath = $mediaFile->store('public/uploads/media'); // Store the media file and get the path
                $fullMediaPath = storage_path('app/' . $mediaPath); // Get the full path of the uploaded file

                // Determine the file MIME type
                $mimeType = $mediaFile->getMimeType();

                // Check if the media is an image
                if (strpos($mimeType, 'image') !== false) {
                    \Log::info($request->message);
                    // Send image message
                    $result = sendWhatsappImageMessage(
                        $request->number,
                        $fullMediaPath, // Path to the uploaded image
                        $request->message, // Caption for the image
                        $mimeType, // MIME type (e.g., image/jpeg)
                        $replyId ? $replyId : null
                    );
                } else {
                    // Send video message
                    $result = sendWhatsappMediaMessage(
                        $request->number,
                        $fullMediaPath, // Full path of the uploaded video file
                        $request->message,
                        $replyId ? $replyId : null
                    );
                }
            } else {
                // Send regular message (text only)
                $result = sendWhatsappMessage(
                    $request->number,
                    array('message' => $request->message),
                    $replyId ? $replyId : null
                );
            }
        }

        // Accessing the result's message id properly, assuming it may be an object

        // Accessing the result's message id properly, assuming it may be an object or array
        $messageId = is_array($result) ? ($result['message']['id'] ?? null) : ($result->message->id ?? null);

        // Log the response and create a webhook response entry
        $response = WebhookResponse::create([
            'status' => 1,
            'name' => 'whatsapp',
            'message' => $request->message,
            'number' => $request->number,
            'from' => $from,
            'read' => !is_null(Auth::guard('admin')) ? 1 : 0,
            'flex' => !is_null(Auth::guard('admin')) ? 'A' : 'C',
            'wa_id' => $replyId ? $replyId : null,
            'res_id' => $messageId,
            'video' => (strpos($mimeType, 'video') !== false) ? basename($mediaPath) : null, // Store video file name if it's a video
            'image' => (strpos($mimeType, 'image') !== false) ? basename($mediaPath) : null, // Store image file name if it's an image
        ]);

        return response()->json([
            'msg' => 'Message sent successfully',
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
        sendWhatsappMessage($request->number, array('name' => '', 'message' => ''));
        $client = Client::where('phone', 'like', '%' . $request->number . '%')->first();
        $_msg = TextResponse::where('status', '1')->where('keyword', 'main_menu')->first();

        WebhookResponse::create([
            'status'        => 1,
            'name'          => 'whatsapp',
            'entry_id'      => '',
            'message'       => $_msg ? ($client && $client->lng == 'en') ? $_msg->eng : $_msg->heb : '',
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
        if ($type == 'number'){
            $data = WebhookResponse::distinct()->where('number', 'like', '%' . $s . '%')
            ->get(['number']);
            
        }else {
            $data = WebhookResponse::distinct()
                ->Where(function ($query) use ($s) {
                    for ($i = 0; $i < count($s); $i++) {
                        $r = str_replace('+', '', $s[$i]);
                        $query->orwhere('number', 'like',  '%' . $r . '%');
                    }
                })
                ->get(['number']);
        }
            
        $clients = [];

        if (count($data) > 0) {
            foreach ($data as $k => $_no) {
                $no = $_no->number;
                $_unreads = WebhookResponse::where(['number' => $no, 'read' => 0])->pluck('read');

                $data[$k]['unread'] = count($_unreads);
                $cl = Client::where('phone', $no)->first();

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
        $type = $request->type; // 'lead' or 'client'
    
        if (is_null($s)) {
            if ($type === 'lead') {
                return Client::all(); // Return all leads
            } else {
                return $this->chats($request); // Existing behavior for clients
            }
        }
    
        // If it's a numeric search, treat it as phone
        if ($type === 'client') {
            if (is_numeric($s)) {
                return $this->chatSearch($s, 'number');
            }else{
                // Search by name
                $cx = explode(' ', $s);
                $fn = $cx[0];
                $ln = $cx[1] ?? $cx[0];

                $clients = Client::where('firstname', 'like', '%' . $fn . '%')
                    ->orWhere('lastname', 'like', '%' . $ln . '%')
                    ->get('phone');

                if (count($clients) > 0) {
                    $nos = [];
                    foreach ($clients as $client) {
                        $nos[] = $client->phone;
                    }

                    return $this->chatSearch($nos, 'name');
                }

                // 👇 Fallback: message search where number exists in Client
                $numbersInMessages = WebhookResponse::where('message', 'like', '%' . $s . '%')
                    ->pluck('number')
                    ->unique();

                $existingClientNumbers = Client::whereIn('phone', $numbersInMessages)->pluck('phone');

                if (count($existingClientNumbers) > 0) {
                    return $this->chatSearch($existingClientNumbers->toArray(), 'name');
                }
            }
        }else{
            $cx = explode(' ', $s);
            $fn = $cx[0];
            $ln = $cx[1] ?? $cx[0];
    
            $leads = Client::where('firstname', 'like', '%' . $fn . '%')
                ->orWhere('lastname', 'like', '%' . $ln . '%')
                ->orWhere('phone', 'like', '%' . $s . '%')
                ->get(['phone', 'firstname', 'lastname']);
    
            if ($leads->count() > 0) {
                return $leads;
            }
    
            // 👇 Fallback: message search where number exists in leads
            $numbersInMessages = WebhookResponse::where('message', 'like', '%' . $s . '%')
                ->pluck('number')
                ->unique();
    
            $matchingLeadNumbers = Client::whereIn('phone', $numbersInMessages)->get(['phone', 'firstname', 'lastname']);
    
            return $matchingLeadNumbers;
        }
       
        return response()->json(['data' => []]);
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

    public function getPageAccessToken()
    {
        $userAccessToken = config('services.facebook.access_token');
        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');

        $permissionsUrl = "https://graph.facebook.com/v18.0/me/permissions?access_token={$userAccessToken}";

        $permissionsResponse = Http::get($permissionsUrl);

        if ($permissionsResponse->successful()) {
            $permissionsData = $permissionsResponse->json();

            $permissions = array_column($permissionsData['data'], 'permission');
            if (!in_array('pages_manage_metadata', $permissions)) {
                Log::error('User access token does not have the necessary permissions.');
                return null;
            }
        } else {
            Log::error('Failed to check user permissions.', ['error' => $permissionsResponse->body()]);
            return null;
        }

        $pageId = config('services.facebook.page_id');
        if (!$pageId) {
            Log::error('Page ID not found in .env file');
            return null;
        }

        $url = "https://graph.facebook.com/v18.0/{$pageId}?fields=access_token&access_token={$userAccessToken}";

        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();
            $pageAccessToken = $data['access_token'];
            \Log::info("Page Access Token: " . $pageAccessToken);

            return $pageAccessToken;
        } else {
            Log::error('Failed to generate Page Access Token', [
                'error' => $response->body()
            ]);

            return null;
        }
    }

    public function subscribePageToApp($pageId)
    {
        $pageAccessToken = $this->getPageAccessToken();

        if ($pageAccessToken) {
            $url = "https://graph.facebook.com/{$pageId}/subscribed_apps";

            $response = Http::get($url, [
                'subscribed_fields' => 'messages',
                'access_token' => $pageAccessToken
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Failed to subscribe Page to app', [
                    'error' => $response->body()
                ]);
                return null;
            }
        } else {
            Log::error('Page Access Token not available');
            return null;
        }
    }

    public function Participants()
    {
        $pageAccessToken = $this->getPageAccessToken();

        if (!$pageAccessToken) {
            Log::error('Page Access Token is not available');
            return response()->json([
                'error' => 'Page Access Token is missing'
            ]);
        }

        $url = 'https://graph.facebook.com/v21.0/' . config('services.facebook.account_id') . '/conversations?fields=participants&limit=100000000000000000000000000000000000000000000000000000&access_token=' . $pageAccessToken;

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
        $pageId = config('services.facebook.page_id');
        $tokenResponse = Http::withToken(config('services.facebook.access_token'))
            ->get("https://graph.facebook.com/v21.0/$pageId", [
                'fields' => 'access_token',
            ]);


        $tokenData = $tokenResponse->json();
        $pageAccessToken = $tokenData['access_token'] ?? null;


        $url = 'https://graph.facebook.com/v21.0/' . $id . '/?fields=participants,messages{id,message,created_time,from}&access_token=' . $pageAccessToken;

        Log::info("Requesting Messenger messages", ["URL" => $url]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            Log::error("CURL error", ["Error" => curl_error($ch)]);
            curl_close($ch);
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
        curl_close($ch);

        $response = json_decode($result, true);

        // Check for Graph API error
        if ($httpCode !== 200 || isset($response['error'])) {
            Log::error("Graph API error", [
                'HTTP Code' => $httpCode,
                'Response' => $response,
            ]);
            return response()->json(['error' => $response['error'] ?? 'Unknown error'], $httpCode);
        }

        Log::info('Messenger messages fetched successfully', [
            'chat' => $response
        ]);

        return response()->json([
            'chat' => $response,
        ]);
    }

    public function messengerReply(Request $request)
    {
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
        if ($chats) {
            return response()->json([
                'msg' => 'Conversation has been deleted!'
            ]);
        } else {
            return response()->json([
                'msg' => 'No conversation found!'
            ], 422);
        }
    }
}
