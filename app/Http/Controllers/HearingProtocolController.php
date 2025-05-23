<?php
namespace App\Http\Controllers;

use App\Models\HearingProtocol;
use App\Models\HearingInvitation;
use App\Models\User;
use App\Models\Claim;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDF;

class HearingProtocolController extends Controller
{
    public function latestHearingInvitation(Request $request)
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
            'pdf_name' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:pdf|max:2048',
            'worker_id' => 'required|exists:users,id',
            'team_id' => 'nullable|exists:admins,id',
            'hearing_invitation_id' => 'required|exists:hearing_invitations,id',
            'comment' => 'nullable|string',
        ]);

        $user = User::findOrFail($validatedData['worker_id']);
        $claims = Claim::where('worker_id', $user->id)->pluck('claim')->toArray();
        $comment = Comment::where('user_id', $user->id)->latest()->value('comment');
        $invitation = HearingInvitation::findOrFail($validatedData['hearing_invitation_id']);
        $lng = $user->lng;

        switch($lng) {
            case 'heb':
                $html = '
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body {
                            font-family: "DejaVu Sans", Arial, sans-serif;
                            font-size: 11pt;
                            line-height: 1.7;
                            direction: rtl;
                            text-align: right;
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
                        ol {
                            list-style-position: inside;
                            padding-right: 0;
                            margin: 0;
                        }
                        ol li {
                            text-align: right;
                        }
                    </style>
                </head>
                <body>
                    <div class="content">
                        <div class="header">פרוטוקול ישיבת שימוע</div>
            
                        <p><strong>שם החברה:</strong> Broom Service</p>
                        <p><strong>שם העובד/ת:</strong> ' . htmlspecialchars($user->firstname) . '</p>
                        <p><strong>מספר ת.ז:</strong> ' . ($user->worker_id ?? 'N/A') . '</p>
                        <p><strong>תפקיד בחברה:</strong> ' . ($user->role ?? 'N/A') . '</p>
                        <p><strong>תאריך תחילת העסקה:</strong> ' . optional($user->created_at)->format('d/m/Y') . '</p>
                        <p><strong>ישיבת השימוע נערכה ביום:</strong> ' . optional($invitation->created_at)->format('l d/m/Y \בְּשָׁעָה H:i') . '</p>
            
                        <p><strong>נוכחים במעמד השימוע:</strong></p>
                        <p><strong>שם:</strong> <span dir="ltr">Hearing Officer</span> | <strong>תפקיד:</strong> <span dir="ltr">HR Officer</span></p>
            
                        <p><strong>נימוקי המעסיק לקיום השימוע:</strong></p>
                        <ol>';
                
                foreach ($claims as $reason) {
                    $html .= '<li>' . htmlspecialchars($reason) . '</li>';
                }
            
                $html .= '</ol>
            
                        <p><strong>טענות העובד/ת:</strong></p>
                        <ol>';
            
                if ($comment) {
                    $html .= '<li>' . htmlspecialchars($comment) . '</li>';
                } else {
                    for ($i = 1; $i <= 6; $i++) {
                        $html .= '<li>____________________________________________________</li>';
                    }
                }
            
                $html .= '</ol>
            
                        <div class="footer">
                            <p><strong>חתימת עורך השימוע:</strong> _________________</p>
                            <p><strong>תפקיד בחברה:</strong> HR Officer</p>
                        </div>
                    </div>
                </body>
                </html>';
                break;

            case 'ru' :
                $html = '
                <html lang="ru">
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body {
                            direction: ltr;
                            font-family: "DejaVu Sans", Arial, sans-serif;
                            font-size: 12pt;
                            line-height: 1.4;
                            text-align: left;
                        }
                        h2 {
                            text-align: center;
                            font-size: 16pt;
                            margin-bottom: 20px;
                        }
                        p {
                            margin-bottom: 8px;
                        }
                        ol {
                            padding-left: 20px;
                        }
                    </style>
                </head>
                <body>
                    <h2>Протокол слушания</h2>
                    <p><strong>Название компании:</strong> Broom Service</p>
                    <p><strong>Имя сотрудника:</strong> ' . htmlspecialchars($user->firstname) . '</p>
                    <p><strong>Идентификационный номер:</strong> ' . ($user->worker_id ?? 'N/A') . '</p>
                    <p><strong>Должность в компании:</strong> ' . ($user->role ?? 'N/A') . '</p>
                    <p><strong>Дата начала транзакции:</strong> ' . optional($user->created_at)->format('d/m/Y') . '</p>
                    <p><strong>Слушание состоялось:</strong> ' . optional($invitation->created_at)->format('l d/m/Y в H:i') . '</p>

                    <p><strong>На заседании присутствовали:</strong></p>
                    <p>ФИО: Hearing Officer| Должность: HR Officer</p>

                    <p><strong>Основания работодателя для проведения слушания:</strong></p>
                    <ol>';
                
                foreach ($claims as $reason) {
                    $html .= '<li>' . htmlspecialchars($reason) . '</li>';
                }

                $html .= '</ol>
                    <p><strong>Претензии работника:</strong></p>
                    <ol>';

                if ($comment) {
                    $html .= '<li>' . htmlspecialchars($comment) . '</li>';
                } else {
                    for ($i = 1; $i <= 6; $i++) {
                        $html .= '<li>__________________________________________________________________</li>';
                    }
                }

                $html .= '</ol>
                    <br><br>
                    <p><strong>Подпись лица, проводящего слушание:</strong> _________________</p>
                    <p><strong>Должность в компании:</strong> HR Officer</p>
                </body>
                </html>';
                break;

            default :
                $html = '
                <html>
                <head>
                    <style>
                        body {
                            font-family: DejaVu Sans, Arial, sans-serif;
                            font-size: 10pt; /* smaller font for general text */
                            line-height: 1.3; /* reduced line height for less vertical spacing */
                        }
                        h2 {
                            text-align: center;
                            margin-bottom: 20px;
                            font-size: 16pt;
                        }
                        .company-name {
                            font-size: 16pt;
                            margin-bottom: 20px;
                        }
                        p {
                            margin-bottom: 6px; /* reduce paragraph spacing */
                        }
                        ul, ol {
                            margin-left: 20px;
                        }
                    </style>
                </head>
                <body>
                    <h2>Minutes of the Hearing</h2>
                    <div class="company-name"><strong>Company Name:</strong> Broom Service</div>
                    <p><strong>Name of the employee:</strong> ' . htmlspecialchars($user->firstname) . '</p>
                    <p><strong>ID number:</strong> ' . ($user->worker_id ?? 'N/A') . '</p>
                    <p><strong>Position in the company:</strong> ' . ($user->role ?? 'N/A') . '</p>
                    <p><strong>Transaction start date:</strong> ' . optional($user->created_at)->format('d/m/Y') . '</p>
                    <p><strong>The hearing was held on:</strong> ' . optional($invitation->created_at)->format('l d/m/Y \a\t H:i') . '</p>
                
                    <p><strong>Present at the hearing:</strong></p>
                    <p>Name: Hearing Officer | Position: HR Officer</p>
                
                    <p><strong>The employer\'s reasons for holding the hearing:</strong></p>
                    <ol>';

                foreach ($claims as $reason) {
                    $html .= '<li>' . htmlspecialchars($reason) . '</li>';
                }

                $html .= '</ol>
                    <p><strong>Claims of the employee:</strong></p>
                    <ol>';

                $html .= '<li>' . htmlspecialchars($comment) . '</li>';

                $html .= '</ol>
                    <br><br>
                    <p><strong>Signature of the hearing officer:</strong> ___________________</p>
                    <p><strong>Position in the company:</strong> HR Officer</p>
                    <br>
                </body>
                </html>';
            break;
        }

        // Convert to PDF
        $pdf = PDF::loadHTML($html);
        $filename = 'hearing_protocols/' . Str::slug($user->name) . '_protocol_' . now()->timestamp . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        // Save record
        $validatedData['file'] = $filename;
        $protocol = HearingProtocol::create($validatedData);

        return response()->json([
            'message' => 'Protocol generated and saved successfully!',
            'path' => Storage::url($filename),
            'data' => $protocol,
        ], 201);
    }

    public function show(Request $request)
    {
        $workerId = $request->query('worker_id');
        if (!$workerId) {
            return response()->json(['message' => 'Worker ID is required.'], 400);
        }
    
        // Find the protocol by worker_id
        $protocol = HearingProtocol::where('worker_id', $workerId)->latest()->first();
    
        if (!$protocol) {
            return response()->json(['message' => 'Protocol not found.'], 404);
        }
    
        // Generate the correct URL using Storage::url()
        $fileUrl = Storage::url($protocol->file);

        return response()->json([
            'id' => $protocol->id,
            'file' => $fileUrl
        ]);
    }
}
