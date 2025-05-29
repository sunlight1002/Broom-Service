<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HearingInvitation;
use App\Models\HearingDecision;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PDF;

class DecisionController extends Controller
{

    public function latest_Hearing_Invitation(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:users,id',
        ]);
    
        $invitation = HearingInvitation::where('user_id', $request->worker_id)
            ->latest()
            ->first();
    
        if (!$invitation) {
            return response()->json(['message' => 'No hearing invitation found.'], 404);
        }
    
        return response()->json(['hearing_invitation_id' => $invitation->id]);
    }    

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'hearing_invitation_id' => 'required|exists:hearing_invitations,id',
            'worker_id' => 'required|exists:users,id',
            'manager_name' => 'required|string',
            'manager_role' => 'required|string',
            'team_id' => 'required|exists:admins,id',
        ]);        

        $invitation = HearingInvitation::findOrFail($validatedData['hearing_invitation_id']);
        $user = User::findOrFail($validatedData['worker_id']);
        $lng = $user->lng;
        $last_work_date = $user->last_work_date;

        $hearingDate = Carbon::parse($invitation->created_at);
        $lastWorkDate = Carbon::parse($last_work_date);
        $daysNotice = $hearingDate->diffInDays($lastWorkDate);

        $decisionDate = now()->format('d/m/Y');
        $hearingDate = optional($invitation->created_at)->format('d/m/Y');
        $employeeName = htmlspecialchars($user->firstname . ' ' . $user->lastname);
        $managerName = $request->input('manager_name');
        $managerRole = $request->input('manager_role');
        $companyName = 'Broom Service';

        switch ($lng) {
            case 'heb':
                $html = '
                <html dir="rtl">
                <head><meta charset="UTF-8">
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.6; direction: rtl; text-align: right; }
                    .header { text-align: center; font-size: 16pt; margin-bottom: 20px; font-weight: bold; }
                </style>
                </head>
                <body>
                <div class="header">החלטה לאחר שימוע</div>
                <p>תאריך: ' . $decisionDate . '</p>
                <p>נמען: ' . $employeeName . '</p>
                <p>נושא: החלטה לאחר שימוע שנערך ביום ' . $hearingDate . '</p>
                <p>שלום ' . $employeeName . ',</p>';

                if ($last_work_date) {
                    $html .= '
                <p>החברה החליטה לסיים את העסקתך בחברה. בהתאם לחוק, עליך לתת הודעה מוקדמת לחברה של ' . $daysNotice . ' ימים.</p>
                <p>למרות זאת, הוחלט לוותר על תקופת ההודעה המוקדמת. אין צורך שתתייצב לעבודה וההודעה המוקדמת תשולם לך במלואה.</p>';
                } else {
                    $html .= '
                <p>החברה החליטה לתת לך הזדמנות נוספת ובכך הוחלט להשאיר אותך בחברה בתפקידך הנוכחי.</p>';
                }

                $html .= '
                <p>אנו מודים לך על הזמן שהקדשת להציג את טיעוניך ועל שיתוף הפעולה במהלך השימוע. ההחלטה התקבלה לאחר שיקול דעת מקיף ומטרתה לשמור על סדרי העבודה והיחסים התקינים במקום העבודה.</p>
                <br><p>בברכה,</p>
                <p>' . $managerName . '</p>
                <p>' . $managerRole . '</p>
                <p>' . $companyName . '</p>
                <p>________________________</p>
                </body>
                </html>';
                break;

            case 'ru':
                $html = '
                <html lang="ru">
                <head><meta charset="UTF-8">
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.6; text-align: left; }
                    .header { text-align: center; font-size: 16pt; margin-bottom: 20px; font-weight: bold; }
                </style>
                </head>
                <body>
                <div class="header">Решение после слушания</div>
                <p>Дата: ' . $decisionDate . '</p>
                <p>Получатель: ' . $employeeName . '</p>
                <p>Тема : Решение после слушания, состоявшегося ' . $hearingDate . '</p>
                <p>Здравствуйте, ' . $employeeName . ',</p>';

                if ($last_work_date) {
                    $html .= '
                <p>Компания приняла решение уволить вас с работы в компании. В соответствии с законом вы должны уведомить компанию за ' . $daysNotice . ' дней.</p>
                <p>Несмотря на это, было решено отказаться от периода предварительного уведомления. Вам не нужно приходить на работу, а предоплата будет выплачена вам в полном объеме.</p>';
                } else {
                    $html .= '
                <p>Компания решила дать вам еще один шанс, и поэтому было решено оставить вас в компании на вашей нынешней должности.</p>';
                }

                $html .= '
                <p>Мы благодарим вас за время, которое вы потратили на изложение своих аргументов, и за ваше сотрудничество во время слушаний. Решение было принято после тщательного рассмотрения и его целью является сохранение рабочего порядка и нормальных отношений на рабочем месте.</p>
                <br><p>С наилучшими пожеланиями,</p>
                <p>' . $managerName . '</p>
                <p>' . $managerRole . '</p>
                <p>' . $companyName . '</p>
                <p>________________________</p>
                </body>
                </html>';
                break;

            default:
                $html = '
                <html>
                <head><meta charset="UTF-8">
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.6; }
                    .header { text-align: center; font-size: 16pt; margin-bottom: 20px; font-weight: bold; }
                </style>
                </head>
                <body>
                <div class="header">Decision after Hearing</div>
                <p>Date: ' . $decisionDate . '</p>
                <p>Recipient: ' . $employeeName . '</p>
                <p>Subject: Decision after a hearing held on ' . $hearingDate . '</p>
                <p>Hello ' . $employeeName . ',</p>';

                if ($last_work_date) {
                    $html .= '
                    <p>The company has decided to terminate your employment with the company. In accordance with the law, you must give the company a ' . $daysNotice .' days’ notice.</p>
                    <p>Despite this, it was decided to waive the advance notice period. There is no need for you to report to work, and the advance notice will be paid to you in full.</p>';
                } else {

                    $html .= '
                    <p>The company has decided to give you another chance and thus it was decided to keep you in the company in your current position.</p>';
                }

                $html .= '
                <p>We thank you for the time you took to present your arguments and for your cooperation during the hearing. The decision was made after extensive consideration and its purpose is to maintain the working order and normal relations in the workplace</p>
                <br>
                <div style="text-align: right;">
                    <p>Best regards,</p>
                    <p>' . $managerName . '</p>
                    <p>' . $managerRole . '</p>
                    <p>' . $companyName . '</p>
                    <p>________________________</p>
                </div>
                </body>
                </html>';
                break;
        }

        $pdf = PDF::loadHTML($html);
        $filename = 'hearing_decisions/' . Str::slug($employeeName) . '_decision_' . now()->timestamp . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        // Save record
        $validatedData['file'] = $filename;
        $decision = HearingDecision::create($validatedData);

        return response()->json([
            'message' => 'Decision generated and saved successfully!',
            'path' => Storage::url($filename),
            'data' => $decision,
        ], 201);
    }

    public function show(Request $request)
    {
        $workerId = $request->query('worker_id');
        if (!$workerId) {
            return response()->json(['message' => 'Worker ID is required.'], 400);
        }
    
        // Find the protocol by worker_id
        $decision = HearingDecision::where('worker_id', $workerId)->latest()->first();
    
        if (!$decision) {
            return response()->json(['message' => 'Protocol not found.'], 404);
        }
    
        // Generate the correct URL using Storage::url()
        $fileUrl = Storage::url($decision->file);

        return response()->json([
            'id' => $decision->id,
            'file' => $fileUrl
        ]);
    }

    public function generateFinalLetter(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $workerId = $data['worker_id'] ?? null;
    
        if (!$workerId) {
            return response()->json(['error' => 'worker_id is required'], 422);
        }
    
        $worker = User::find($workerId);
        $user = User::find($workerId);
    
        if (!$worker) {
            return response()->json(['error' => "Worker not found."], 404);
        }
    
        $company = [
            'name' => 'Broom Service',
            'address' => '123 Company Street, City, Country',
            'contact' => 'office@broomservice.co.il | +972 03-525-70-60',
        ];
    
        $employmentStart = $worker->first_date
            ? Carbon::parse($worker->first_date)->translatedFormat('F j, Y')
            : 'N/A';
    
        $employmentEnd = $worker->last_work_date
            ? Carbon::parse($worker->last_work_date)->translatedFormat('F j, Y')
            : 'N/A';
    
        $lng = $worker->lng;
        $html = '';
    
        switch ($lng) {
            case 'heb':
                $html = '
                <html dir="rtl">
                <head><meta charset="UTF-8">
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.6; direction: rtl; }
                    .header { text-align: center; font-size: 16pt; margin-bottom: 20px; font-weight: bold; }
                </style>
                </head>
                <body>
                    <div class="header">מכתב סיום העסקה</div>
                    <p>לכל המעוניין,</p>
                    <p>מכתב זה מאשר כי <strong>' . htmlspecialchars($worker->full_name) . '</strong> הועסק/ה בחברת <strong>' . htmlspecialchars($company['name']) . '</strong> בתפקיד <strong>' . htmlspecialchars($worker->role) . '</strong>.</p>
                    <p>תקופת ההעסקה הייתה מ-<strong>' . $employmentStart . '</strong> ועד <strong>' . $employmentEnd . '</strong>.</p>
                    <p>מכתב זה ניתן בצירוף תלוש השכר האחרון ומאשר את סיום ההעסקה.</p>
                    <br>
                    <div style="text-align: left;">
                        <p>בברכה,</p>
                        <p>מחלקת משאבי אנוש</p>
                        <p>' . htmlspecialchars($company['name']) . '</p>
                    </div>
                </body>
                </html>';                
                break;
    
            case 'ru':
                $html = '
                <html lang="ru">
                <head><meta charset="UTF-8">
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.6; direction: ltr; text-align: left; }
                    .header { text-align: center; font-size: 16pt; margin-bottom: 20px; font-weight: bold; }
                </style>
                </head>
                <body>
                    <div class="header">Заключительное письмо о трудоустройстве</div>
                    <p>Кого это может касаться,</p>
                    <p>Настоящим подтверждаем, что <strong>' . htmlspecialchars($worker->full_name) . '</strong> работал(а) в компании <strong>' . htmlspecialchars($company['name']) . '</strong> в должности <strong>' . htmlspecialchars($worker->role) . '</strong>.</p>
                    <p>Период работы: с <strong>' . $employmentStart . '</strong> по <strong>' . $employmentEnd . '</strong>.</p>
                    <p>Это письмо предоставляется вместе с окончательным расчетным листом и подтверждает завершение трудовых отношений.</p>
                    <br>
                    <div style="text-align: right;">
                        <p>С уважением,</p>
                        <p>Отдел кадров</p>
                        <p>' . htmlspecialchars($company['name']) . '</p>
                    </div>
                </body>
                </html>';
                break;
    
            default:
                $html = '
                <html dir="ltr">
                <head><meta charset="UTF-8">
                <style>
                    body { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1.6; direction: ltr; }
                    .header { text-align: center; font-size: 16pt; margin-bottom: 20px; font-weight: bold; }
                </style>
                </head>
                <body>
                    <div class="header">Final Employment Letter</div>
                    <p>To whom it may concern,</p>
                    <p>This letter confirms that <strong>' . htmlspecialchars($worker->full_name) . '</strong> was employed at <strong>' . htmlspecialchars($company['name']) . '</strong> in the position of <strong>' . htmlspecialchars($worker->role) . '</strong>.</p>
                    <p>The employment period was from <strong>' . $employmentStart . '</strong> to <strong>' . $employmentEnd . '</strong>.</p>
                    <p>This letter is provided together with the final payslip and confirms the end of employment.</p>
                    <br>
                    <div style="text-align: right;">
                        <p>Best regards,</p>
                        <p>HR Department</p>
                        <p>' . htmlspecialchars($company['name']) . '</p>
                    </div>
                </body>
                </html>';
                break;
        }

        $pdf = PDF::loadHTML($html)->setPaper('a4');

        $documentCount = $user->documents()->count() + 1;

        $uniqueName = $user->id . '_' . $documentCount . '.pdf';
        $filename = 'final_letters/' . $uniqueName;

        Storage::disk('public')->put($filename, $pdf->output());

        $documentTypeId = DocumentType::where('name', 'Final Employment Letter')->value('id') ?? 9;

        $user->documents()->create([
            'document_type_id' => $documentTypeId,
            'name' => 'Final Employment Letter',
            'date' => now(),
            'file' => $filename,
        ]);

        return response()->json([
            'message' => 'Final letter generated successfully!',
            'path' => Storage::url($filename),
        ], 201);
    }
}
