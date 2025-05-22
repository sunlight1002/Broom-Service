<?php

namespace App\Http\Controllers;

use App\Models\HearingInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Schedule;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use App\Traits\GoogleAPI;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;


class HearingInvitationController extends Controller
{
    /**
     * Display the specified hearing invitation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }

        return response()->json(['schedule' => $invitation], 200);
    }

    /**
     * Store a newly created hearing invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        \Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'team_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'meet_via' => 'required|string',
            'meet_link' => 'nullable|string',
            'purpose' => 'nullable|string',
            'booking_status' => 'nullable|string',
            'address_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hebrewMeridianMap = [
            'לפנה"צ' => 'AM',
            'בבוקר' => 'AM',
            'לפני הצהריים' => 'AM',
            'לפנות בוקר' => 'AM',
            'אחה"צ' => 'PM',
            'אחרי הצהריים' => 'PM',
            'בערב' => 'PM',
        ];

        $startTimeInput = $request->input('start_time');

        foreach ($hebrewMeridianMap as $hebrew => $english) {
            if (str_contains($startTimeInput, $hebrew)) {
                $startTimeInput = str_replace($hebrew, $english, $startTimeInput);
                break;
            }
        }

        try {
            $startTime = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $startTimeInput)->format('h:i A');
            $endTime = Carbon::parse($startTime)->addMinutes(30)->format('h:i A');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid time format'], 422);
        }

        $team = Admin::find($request->input('team_id'));
        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }
        $worker = User::find($request->input('user_id'));
        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $teamId = $team->id ?? null;
        $teamName = $team->name ?? null;

        if ($worker && $worker->lng == "heb") {
            $teamName = $team ? $team->heb_name : null;
        } else {
            $teamName = $team ? $team->name : null;
        }


        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $purposes = $request->input('purpose');

        $formattedPurposes = '';
        if (is_array($purposes)) {
            foreach ($purposes as $index => $text) {
                $formattedPurposes .= ($index + 1) . '. ' . trim($text) . '<br>';
            }
        } else {
            $formattedPurposes = $purposes;
        }

        $invitation = HearingInvitation::create([
            'user_id' => $request->input('user_id'),
            'team_id' => $teamId,
            'start_date' => $request->input('start_date'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'meet_via' => $request->input('meet_via'),
            'meet_link' => $request->input('meet_link'),
            'purpose' => $formattedPurposes,
        ]);


        $htmlContent = '';
        switch ($worker->lng) {
            case 'heb':
                $purposes = $request->input('purpose');

                if (!is_array($purposes)) {
                    $purposes = preg_split('/[.,]/', $purposes);
                    $purposes = array_filter(array_map('trim', $purposes));
                }

                $purposeListHtml = implode('', array_map(function ($text, $i) {
                    return '<li>' . htmlspecialchars($text) . '</li>';
                }, $purposes, array_keys($purposes)));

                $htmlContent = '
                <html>
                <head>
                    <style>
                        body {
                            font-family: DejaVu Sans, Arial, sans-serif;
                            font-size: 11pt;
                            line-height: 1.7;
                        }
                        .header {
                            text-align: center;
                            font-size: 16pt;
                            font-weight: bold;
                            margin-bottom: 25px;
                        }
                        .content {
                            margin: 20px;
                        }
                        .content p {
                            margin-bottom: 10px;
                        }
                        .footer {
                            margin-top: 40px;
                            text-align: right;
                        }
                        .date {
                            text-align: right;
                        }
                        .honor {
                            text-align: right; 
                        }
                        ol {
                            margin-right: 20px;
                        }
                        ol li {
                            text-align: right;
                        }
                    </style>
                </head>
                <body style="direction: rtl; text-align: right;">
                    <div class="content" style="direction: rtl; text-align: right;">
                        <p class="date" style="direction: rtl; text-align: right;">תאריך: ' . $request->input('start_date') . '</p>
                        <p class="honor" style="direction: rtl; text-align: right;">לכבוד: ' . ($worker->firstname . ' ' . $worker->lastname) . '</p>
                        <div class="header" style="direction: rtl; text-align: right;">זימון לישיבת שימוע</div>
                        <p style="direction: rtl; text-align: right;">הרינו להודיעך, כי ביום ' . $request->input('start_date') . ', בשעה ' . $startTime . ' תיערך לך ישיבת שימוע בפני גב\'/מר ' . ($teamName ?? 'לא צויין') . ', ' . ($teamName ? 'מנהל צוות' : 'תפקיד לא צויין') . ', במשרדי החברה, במטרה לשקול את המשך העסקתך בחברה, וזאת מן הטעמים הבאים:</p>
                        <ol style="
                            direction: rtl; 
                            text-align: right; 
                            list-style-position: inside; 
                            padding-right: 0; 
                            margin-left: 0;
                            margin-right: 0;
                        ">
                            ' . $purposeListHtml . '
                        </ol>
                        <p style="direction: rtl; text-align: right;">לידיעתך, בישיבת השימוע יכול להשתתף גם מלווה/עורך דין מטעמך. כמו כן, הנך רשאי להגיב בכתב לנטען במכתב זה ולצרף כל מסמך התומך בטיעוניך.</p>
                        <p style="direction: rtl; text-align: right;">השימוע יתנהל בפתיחות ובתום לב, ואנו נשקול את בקשותיך/טענותיך, ככל שניתן.</p>
                    </div>
                    <div class="footer" style="direction: rtl; text-align: right;">
                        <p style="direction: rtl; text-align: right;">בכבוד רב,</p>
                        <p style="direction: rtl; text-align: right;">' . ($teamName ?? 'לא צויין') . '</p>
                    </div>
                </body>
                </html>';
                break;


            case 'ru':
                $purposes = $request->input('purpose');

                if (!is_array($purposes)) {
                    $purposes = preg_split('/[.,]/', $purposes);
                    $purposes = array_filter(array_map('trim', $purposes));
                }

                $purposeListHtml = implode('', array_map(function ($text, $i) {
                    return '<li>' . htmlspecialchars($text) . '</li>';
                }, $purposes, array_keys($purposes)));

                $htmlContent = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.7; }
                        .header { text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 25px; }
                        .content { margin: 20px; }
                        .content p { margin-bottom: 10px; }
                        .footer { margin-top: 40px; text-align: left; }
                        .date { text-align: right; }
                        .honor { text-align: left; }
                        ol { margin-left: 20px; }
                    </style>
                </head>
                <body>
                    <div class="content">
                        <p class="date">Дата: ' . $request->input('start_date') . '</p>
                        <p class="honor">В честь: ' . ($worker->firstname . ' ' . $worker->lastname) . '</p>
                        <div class="header">Вызов на слушание</div>
                        <p>Мы хотели бы сообщить вам, что ' . $request->input('start_date') . ', в ' . $startTime . ', перед г-жой/г-ном ' . ($teamName ?? 'Не указано') . ', ' . ($teamName ? 'руководитель команды' : 'Должность не указана') . ', в офисе компании будет проведено слушание для рассмотрения вашего дальнейшего трудоустройства в компании по следующим причинам:</p>
                        <ol style="direction: ltr; text-align: left; list-style-position: inside; padding-left: 7px; margin-left: 0;">
                            ' . $purposeListHtml . '
                        </ol>
                        <p>К вашему сведению, кредитор/юрист также может участвовать в слушании от вашего имени. Кроме того, вы можете ответить в письменном виде на то, что утверждается в этом письме, и приложить любой документ, подтверждающий ваши аргументы.</p>
                        <p>Слушание будет проводиться открыто и добросовестно, и мы учтем ваши запросы/претензии, насколько это возможно.</p>
                    </div>
                    <div class="footer">
                        <p>Искренне,</p>
                        <p>' . ($teamName ?? 'Не указано') . '</p>
                    </div>
                </body>
                </html>';
                break;

            default:
                $purposes = $request->input('purpose');

                if (!is_array($purposes)) {
                    $purposes = preg_split('/[.,]/', $purposes);
                    $purposes = array_filter(array_map('trim', $purposes));
                }

                $purposeListHtml = implode('', array_map(function ($text, $i) {
                    return '<li>' . htmlspecialchars($text) . '</li>';
                }, $purposes, array_keys($purposes)));

                $htmlContent = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.7; }
                        .header { text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 25px; }
                        .content { margin: 20px; }
                        .content p { margin-bottom: 10px; }
                        .footer { margin-top: 40px; text-align: right; }
                        .date { text-align: right; }
                        .honor { text-align: left; }
                        ol { margin-left: 20px; }
                    </style>
                </head>
                <body>
                    <div class="content">
                        <p class="date">Date: ' . $request->input('start_date') . '</p>
                        <p class="honor">In honor of: ' . ($worker->firstname . ' ' . $worker->lastname) . '</p>
                        <div class="header">Summons for a Hearing</div>
                        <p>We would like to inform you that on ' . $request->input('start_date') . ', at ' . $startTime . ', a hearing will be held for you before Ms./Mr. ' . ($teamName ?? 'Not specified') . ', ' . ($teamName ? 'Team Manager' : 'Position not specified') . ', at the company\'s offices, in order to consider your continued employment in the company, for the following reasons:</p>
                        <ol style="direction: ltr; text-align: left; list-style-position: inside; padding-left: 7px; margin-left: 0;">
                            ' . $purposeListHtml . '
                        </ol>
                        <p>For your information, a lender/lawyer can also participate in the hearing on your behalf. Also, you may respond in writing to what is claimed in this letter and attach any document that supports your arguments.</p>
                        <p>The hearing will be conducted openly and in good faith, and we will consider your requests/claims, as much as possible.</p>
                    </div>
                    <div class="footer">
                        <p>Sincerely,</p>
                        <p>' . ($teamName ?? 'Not specified') . '</p>
                    </div>
                </body>
                </html>';
                break;
        }

        // Generate and save the PDF
        $pdf = PDF::loadHTML($htmlContent)->setOptions(['defaultFont' => 'DejaVu Sans']);
        $pdf->setPaper('A4');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);
        $pdfPath = 'hearing_protocols/hearing_invitation_' . $invitation->id . '.pdf';
        Storage::makeDirectory('public/hearing_protocols');
        $pdf->save(storage_path("app/public/" . $pdfPath));

        // Update the invitation record with the file path
        $invitation->update(['file' => $pdfPath]);

        // Continue with notification logic
        if ($worker) {
            $notificationData = [
                'worker' => [
                    'phone' => $worker->phone,
                    'lng' => $worker->lng,
                    'firstname' => $worker->firstname,
                    'lastname' => $worker->lastname,
                ],
                'start_date' => $request->input('start_date'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'purpose' => $request->input('purpose'),
                'team' => $team,
                'id' => $invitation->id,
            ];
            \Log::info($teamName);
            \Log::info($notificationData);
            event(new WhatsappNotificationEvent([
                'type' => WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE,
                'notificationData' => $notificationData
            ]));

            App::setlocale($worker->lng == "heb" ? "heb" : "en");

            Mail::send('/Mails/worker/WorkerHearingMail', ["data" => $notificationData], function ($message) use ($notificationData, $worker) {
                $message->to($worker->email);
                $message->bcc(config('services.mail.default'));
                $message->subject(__('mail.hearing.subject'));
            });
        }

        return response()->json(['message' => 'Hearing Invitation created successfully', 'data' => $invitation], 201);
    }

    /**
     * Update the specified hearing invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $hebrewMeridianMap = [
            'לפנה"צ' => 'AM',
            'בבוקר' => 'AM',
            'לפני הצהריים' => 'AM',
            'לפנות בוקר' => 'AM',
            'אחה"צ' => 'PM',
            'אחרי הצהריים' => 'PM',
            'בערב' => 'PM',
        ];

        $startTimeInput = $request->input('start_time');

        foreach ($hebrewMeridianMap as $hebrew => $english) {
            if (str_contains($startTimeInput, $hebrew)) {
                $startTimeInput = str_replace($hebrew, $english, $startTimeInput);
                break;
            }
        }

        try {
            $startTime = Carbon::createFromFormat('Y-m-d h:i A', date('Y-m-d') . ' ' . $startTimeInput)->format('h:i A');
            $endTime = Carbon::parse($startTime)->addMinutes(30)->format('h:i A');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid time format'], 422);
        }

        $team = Admin::find($request->input('team_id'));
        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }
        $worker = User::find($request->input('user_id'));
        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $teamId = $team->id ?? null;
        $teamName = $team->name ?? null;

        if ($worker && $worker->lng == "heb") {
            $teamName = $team ? $team->heb_name : null;
        } else {
            $teamName = $team ? $team->name : null;
        }


        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $purposes = $request->input('purpose');

        $formattedPurposes = '';
        if (is_array($purposes)) {
            foreach ($purposes as $index => $text) {
                $formattedPurposes .= ($index + 1) . '. ' . trim($text) . '<br>';
            }
        } else {
            $formattedPurposes = $purposes;
        }


        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'team_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'meet_via' => 'required|string',
            'meet_link' => 'nullable|string',
            'purpose' => 'nullable|string',
            'booking_status' => 'nullable|string',
            'address_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startDateTime = Carbon::createFromFormat('Y-m-d h:i A', $request->input('start_date') . ' ' . $request->input('start_time'));
        $endDateTime = $startDateTime->copy()->addMinutes(30);

        $purposes = $request->input('purpose');

        $formattedPurposes = '';
        if (is_array($purposes)) {
            foreach ($purposes as $index => $text) {
                $formattedPurposes .= ($index + 1) . '. ' . trim($text) . '<br>';
            }
        } else {
            $formattedPurposes = $purposes;
        }

        $invitation->update([
            'user_id' => $request->input('user_id'),
            'team_id' => $request->input('team_id'),
            'start_date' => $request->input('start_date'),
            'start_time' => $startDateTime->format('h:i A'),
            'end_time' => $endDateTime->format('h:i A'),
            'meet_via' => $request->input('meet_via'),
            'meet_link' => $request->input('meet_link'),
            'purpose' => $formattedPurposes,
            'booking_status' => $request->input('booking_status'),
            'address_id' => $request->input('address_id'),
        ]);

        $htmlContent = '';
        switch ($worker->lng) {
            case 'heb':
                $purposes = $request->input('purpose');

                if (!is_array($purposes)) {
                    $purposes = preg_split('/[.,]/', $purposes);
                    $purposes = array_filter(array_map('trim', $purposes));
                }

                $purposeListHtml = implode('', array_map(function ($text, $i) {
                    return '<li>' . htmlspecialchars($text) . '</li>';
                }, $purposes, array_keys($purposes)));

                $htmlContent = '
                <html>
                <head>
                    <style>
                        body {
                            font-family: DejaVu Sans, Arial, sans-serif;
                            font-size: 11pt;
                            line-height: 1.7;
                        }
                        .header {
                            text-align: center;
                            font-size: 16pt;
                            font-weight: bold;
                            margin-bottom: 25px;
                        }
                        .content {
                            margin: 20px;
                        }
                        .content p {
                            margin-bottom: 10px;
                        }
                        .footer {
                            margin-top: 40px;
                            text-align: right;
                        }
                        .date {
                            text-align: right;
                        }
                        .honor {
                            text-align: right; 
                        }
                        ol {
                            margin-right: 20px;
                        }
                        ol li {
                            text-align: right;
                        }
                    </style>
                </head>
                <body style="direction: rtl; text-align: right;">
                    <div class="content" style="direction: rtl; text-align: right;">
                        <p class="date" style="direction: rtl; text-align: right;">תאריך: ' . $request->input('start_date') . '</p>
                        <p class="honor" style="direction: rtl; text-align: right;">לכבוד: ' . ($worker->firstname . ' ' . $worker->lastname) . '</p>
                        <div class="header" style="direction: rtl; text-align: right;">זימון לישיבת שימוע</div>
                        <p style="direction: rtl; text-align: right;">הרינו להודיעך, כי ביום ' . $request->input('start_date') . ', בשעה ' . $startTime . ' תיערך לך ישיבת שימוע בפני גב\'/מר ' . ($teamName ?? 'לא צויין') . ', ' . ($teamName ? 'מנהל צוות' : 'תפקיד לא צויין') . ', במשרדי החברה, במטרה לשקול את המשך העסקתך בחברה, וזאת מן הטעמים הבאים:</p>
                        <ol style="
                            direction: rtl; 
                            text-align: right; 
                            list-style-position: inside; 
                            padding-right: 0; 
                            margin-left: 0;
                            margin-right: 0;
                        ">
                            ' . $purposeListHtml . '
                        </ol>
                        <p style="direction: rtl; text-align: right;">לידיעתך, בישיבת השימוע יכול להשתתף גם מלווה/עורך דין מטעמך. כמו כן, הנך רשאי להגיב בכתב לנטען במכתב זה ולצרף כל מסמך התומך בטיעוניך.</p>
                        <p style="direction: rtl; text-align: right;">השימוע יתנהל בפתיחות ובתום לב, ואנו נשקול את בקשותיך/טענותיך, ככל שניתן.</p>
                    </div>
                    <div class="footer" style="direction: rtl; text-align: right;">
                        <p style="direction: rtl; text-align: right;">בכבוד רב,</p>
                        <p style="direction: rtl; text-align: right;">' . ($teamName ?? 'לא צויין') . '</p>
                    </div>
                </body>
                </html>';
                break;


            case 'ru':
                $purposes = $request->input('purpose');

                if (!is_array($purposes)) {
                    $purposes = preg_split('/[.,]/', $purposes);
                    $purposes = array_filter(array_map('trim', $purposes));
                }

                $purposeListHtml = implode('', array_map(function ($text, $i) {
                    return '<li>' . htmlspecialchars($text) . '</li>';
                }, $purposes, array_keys($purposes)));

                $htmlContent = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.7; }
                        .header { text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 25px; }
                        .content { margin: 20px; }
                        .content p { margin-bottom: 10px; }
                        .footer { margin-top: 40px; text-align: left; }
                        .date { text-align: right; }
                        .honor { text-align: left; }
                        ol { margin-left: 20px; }
                    </style>
                </head>
                <body>
                    <div class="content">
                        <p class="date">Дата: ' . $request->input('start_date') . '</p>
                        <p class="honor">В честь: ' . ($worker->firstname . ' ' . $worker->lastname) . '</p>
                        <div class="header">Вызов на слушание</div>
                        <p>Мы хотели бы сообщить вам, что ' . $request->input('start_date') . ', в ' . $startTime . ', перед г-жой/г-ном ' . ($teamName ?? 'Не указано') . ', ' . ($teamName ? 'руководитель команды' : 'Должность не указана') . ', в офисе компании будет проведено слушание для рассмотрения вашего дальнейшего трудоустройства в компании по следующим причинам:</p>
                        <ol style="direction: ltr; text-align: left; list-style-position: inside; padding-left: 7px; margin-left: 0;">
                            ' . $purposeListHtml . '
                        </ol>
                        <p>К вашему сведению, кредитор/юрист также может участвовать в слушании от вашего имени. Кроме того, вы можете ответить в письменном виде на то, что утверждается в этом письме, и приложить любой документ, подтверждающий ваши аргументы.</p>
                        <p>Слушание будет проводиться открыто и добросовестно, и мы учтем ваши запросы/претензии, насколько это возможно.</p>
                    </div>
                    <div class="footer">
                        <p>Искренне,</p>
                        <p>' . ($teamName ?? 'Не указано') . '</p>
                    </div>
                </body>
                </html>';
                break;

            default:
                $purposes = $request->input('purpose');

                if (!is_array($purposes)) {
                    $purposes = preg_split('/[.,]/', $purposes);
                    $purposes = array_filter(array_map('trim', $purposes));
                }

                $purposeListHtml = implode('', array_map(function ($text, $i) {
                    return '<li>' . htmlspecialchars($text) . '</li>';
                }, $purposes, array_keys($purposes)));

                $htmlContent = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.7; }
                        .header { text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 25px; }
                        .content { margin: 20px; }
                        .content p { margin-bottom: 10px; }
                        .footer { margin-top: 40px; text-align: right; }
                        .date { text-align: right; }
                        .honor { text-align: left; }
                        ol { margin-left: 20px; }
                    </style>
                </head>
                <body>
                    <div class="content">
                        <p class="date">Date: ' . $request->input('start_date') . '</p>
                        <p class="honor">In honor of: ' . ($worker->firstname . ' ' . $worker->lastname) . '</p>
                        <div class="header">Summons for a Hearing</div>
                        <p>We would like to inform you that on ' . $request->input('start_date') . ', at ' . $startTime . ', a hearing will be held for you before Ms./Mr. ' . ($teamName ?? 'Not specified') . ', ' . ($teamName ? 'Team Manager' : 'Position not specified') . ', at the company\'s offices, in order to consider your continued employment in the company, for the following reasons:</p>
                        <ol style="direction: ltr; text-align: left; list-style-position: inside; padding-left: 7px; margin-left: 0;">
                            ' . $purposeListHtml . '
                        </ol>
                        <p>For your information, a lender/lawyer can also participate in the hearing on your behalf. Also, you may respond in writing to what is claimed in this letter and attach any document that supports your arguments.</p>
                        <p>The hearing will be conducted openly and in good faith, and we will consider your requests/claims, as much as possible.</p>
                    </div>
                    <div class="footer">
                        <p>Sincerely,</p>
                        <p>' . ($teamName ?? 'Not specified') . '</p>
                    </div>
                </body>
                </html>';
                break;
        }

        // Generate and save the PDF
        $pdf = PDF::loadHTML($htmlContent)->setOptions(['defaultFont' => 'DejaVu Sans']);
        $pdf->setPaper('A4');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);
        $pdfPath = 'hearing_protocols/hearing_invitation_' . $invitation->id . '.pdf';
        Storage::makeDirectory('public/hearing_protocols');
        $pdf->save(storage_path("app/public/" . $pdfPath));

        // Update the invitation record with the file path
        $invitation->update(['file' => $pdfPath]);

        // Continue with notification logic
        if ($worker) {
            $notificationData = [
                'worker' => [
                    'phone' => $worker->phone,
                    'lng' => $worker->lng,
                    'firstname' => $worker->firstname,
                    'lastname' => $worker->lastname,
                ],
                'start_date' => $request->input('start_date'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'purpose' => $request->input('purpose'),
                'team_name' => $teamName,
                'id' => $invitation->id,
            ];
            \Log::info('Notification data: ' . json_encode($notificationData));
            event(new WhatsappNotificationEvent([
                'type' => WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE,
                'notificationData' => $notificationData
            ]));
        }

        return response()->json(['message' => 'Hearing Invitation updated successfully', 'data' => $invitation], 200);
    }

    /**
     * Create a new event for the specified hearing invitation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createEvent($id)
    {
        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }

        return response()->json(['message' => 'Event created successfully for hearing invitation', 'data' => $invitation], 201);
    }

    public function index(Request $request)
    {
        $query = HearingInvitation::query()
            ->leftJoin('admins', 'hearing_invitations.team_id', '=', 'admins.id')
            ->leftJoin('users', 'hearing_invitations.user_id', '=', 'users.id')
            ->select(
                'hearing_invitations.id',
                'hearing_invitations.start_date',
                'hearing_invitations.start_time',
                'hearing_invitations.end_time',
                'hearing_invitations.booking_status',
                'admins.name as attender_name',
                'users.firstname',
                'users.lastname',
                'users.phone',
                'users.address',
                'users.id as worker_id'
            );

        // Filter by worker ID if provided
        if ($request->has('worker_id')) {
            $query->where('hearing_invitations.user_id', $request->input('worker_id'));
        }

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search')) {
                    $keyword = $request->get('search')['value'];
                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->whereRaw("CONCAT_WS(' ', users.firstname, users.lastname) like ?", ["%{$keyword}%"])
                                ->orWhere('users.address', 'like', "%" . $keyword . "%")
                                ->orWhere('users.phone', 'like', "%" . $keyword . "%")
                                ->orWhere('admins.name', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->editColumn('name', function ($data) {
                return $data->firstname . ' ' . $data->lastname;
            })
            ->filterColumn('name', function ($query, $keyword) {
                $sql = "CONCAT_WS(' ', users.firstname, users.lastname) like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->orderColumn('name', function ($query, $order) {
                $query->orderBy('users.firstname', $order);
            })
            ->orderColumn('start_date', function ($query, $order) {
                $query->orderBy('hearing_invitations.start_date', $order)
                    ->orderBy('hearing_invitations.start_time', $order);
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function getScheduledHearings($id)
    {
        $hearing = HearingInvitation::find($id);

        if (!$hearing) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }

        return response()->json($hearing);
    }

    public function destroy($id)
    {
        $invitation = HearingInvitation::find($id);

        if (!$invitation) {
            return response()->json(['message' => 'Hearing Invitation not found'], 404);
        }
        $invitation->delete();

        return response()->json(['message' => 'Hearing Invitation deleted successfully'], 200);
    }
}
