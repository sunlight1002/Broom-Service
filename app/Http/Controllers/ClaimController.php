<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\HearingInvitation;
use App\Models\User;
use App\Models\Admin;
use App\Models\HearingProtocol;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PDF;

class ClaimController extends Controller
{
    // public function store(Request $request)
    // {
    //     // Validate the incoming request
    //     $request->validate([
    //         'worker_id' => 'required|exists:users,id',
    //         'admin_id' => 'required|exists:admins,id',
    //         'claim' => 'required|string',
    //         'hearing_invitation_id' => 'required|exists:hearing_invitations,id',
    //     ]);
    
    //     // Extract the request parameters
    //     $workerId = $request->worker_id;
    //     $adminId = $request->admin_id;
    //     $claimText = $request->claim;
    //     $hearingInvitationId = $request->hearing_invitation_id;
    
    //     // Create the claim record
    //     $claim = Claim::create([
    //         'worker_id' => $workerId,
    //         'admin_id' => $adminId,
    //         'claim' => $claimText,
    //         'hearing_invitation_id' => $hearingInvitationId,
    //     ]);
    
    //     // Log claim creation for debugging
    //     \Log::info('Claim created', ['claim_id' => $claim->id]);
    
    //     // Fetch the hearing invitation directly using the passed hearing_invitation_id
    //     $hearingInvitation = HearingInvitation::find($hearingInvitationId);
        
    //     if ($hearingInvitation) {
    //         \Log::info('Hearing Invitation found', [
    //             'hearing_invitation_id' => $hearingInvitation->id,
    //             'worker_id' => $hearingInvitation->user_id,
    //             'admin_id' => $hearingInvitation->team_id,
    //         ]);
    //     } else {
    //         \Log::info('Hearing Invitation not found', [
    //             'worker_id' => $workerId,
    //             'admin_id' => $adminId,
    //         ]);
    //     }
    
    //     if ($hearingInvitation) {
    //         try {
    //             // Fetch the necessary models for hearing, worker, and admin
    //             $worker = User::findOrFail($hearingInvitation->user_id);
    //             $admin = Admin::findOrFail($hearingInvitation->team_id);
    
    //             // Log worker and admin information for debugging
    //             \Log::info('Worker and Admin details fetched', [
    //                 'worker_name' => $worker->firstname,
    //                 'admin_name' => $admin->name
    //             ]);
    
    //             // Prepare data for the PDF
    //             $companyName = $worker->company_type ?? 'Company Name';
    //             $employeeName = $worker->firstname;
    //             $position = $worker->role;
    //             $transactionStartDate = $worker->created_at;
    //             $hearingDate = $hearingInvitation->start_date;
    //             $hearingTime = $hearingInvitation->start_time;
    //             $presentMembers = $hearingInvitation->present_members;
    //             $employerReasons = $hearingInvitation->purpose;
    //             $employeeClaims = $hearingInvitation->employee_claims;
    //             $officerName = $admin->name;
    //             $officerPosition = $admin->role;
    
    //             // Build the HTML content for the PDF
    //             $html = "<html lang='en'>
    //                     <head>
    //                         <meta charset='UTF-8'>
    //                         <title>Minutes of the Hearing</title>
    //                         <style>
    //                             body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; }
    //                             .header { text-align: center; margin-bottom: 20px; }
    //                             .section { margin-bottom: 20px; }
    //                             .signature { margin-top: 40px; }
    //                         </style>
    //                     </head>
    //                     <body>
    //                         <div class='header'>
    //                             <h2>Minutes of the Hearing</h2>
    //                         </div>
    //                         <div class='section'>
    //                             <p><strong>Company Name:</strong> {$companyName}</p>
    //                             <p><strong>Name of the Employee:</strong> {$employeeName}</p>
    //                             <p><strong>Position in the Company:</strong> {$position}</p>
    //                             <p><strong>Transaction Start Date:</strong> {$transactionStartDate}</p>
    //                             <p><strong>The Hearing was held on:</strong> {$hearingDate} at {$hearingTime}</p>
    //                         </div>
    //                         <div class='section'>
    //                             <h4>Present at the Hearing:</h4>";
    //             foreach ($presentMembers as $member) {
    //                 $html .= "<p>Name: {$member['name']} Position: {$member['position']}</p>";
    //             }
    //             $html .= "</div>
    //             <div class='section'>
    //                 <h4>The Employer's Reasons for Holding the Hearing:</h4>";
    //             foreach ($employerReasons as $index => $reason) {
    //                 $html .= "<p>" . ($index + 1) . ". {$reason}</p>";
    //             }
    //             $html .= "</div>
    //             <div class='section'>
    //                 <h4>Claims of the Employee:</h4>";
    //             foreach ($employeeClaims as $index => $claim) {
    //                 $html .= "<p>" . ($index + 1) . ". {$claim}</p>";
    //             }
    //             $html .= "</div>
    //             <div class='signature'>
    //                 <p><strong>Signature of the Hearing Officer:</strong> {$officerName}</p>
    //                 <p><strong>Position in the Company:</strong> {$officerPosition}</p>
    //             </div>
    //             <p>A copy of this protocol will be given to the employee</p>
    //             </body>
    //             </html>";
    
    //             $options = new Options();
    //             $options->set('isHtml5ParserEnabled', true);
    //             $dompdf = new Dompdf($options);
    
    //             $dompdf->loadHtml($html);
    //             $dompdf->setPaper('A4', 'portrait');
    //             $dompdf->render();
    
    //             $pdfContent = $dompdf->output();
    
    //             $pdfFilename = 'hearing_minutes_' . $claim->id . '.pdf';
    //             $pdfPath = 'app/custom_folder/' . $pdfFilename;
    
    //             // Log the PDF path
    //             \Log::info('Generated PDF content', ['pdf_path' => $pdfPath]);
    
    //             Storage::disk('public')->put($pdfPath, $pdfContent);
    
    //             if (Storage::disk('public')->exists($pdfPath)) {
    //                 \Log::info('PDF successfully stored at: ' . $pdfPath);
    //             } else {
    //                 \Log::error('Failed to store PDF at: ' . $pdfPath);
    //             }
    
    //             $claim->file = 'storage/' . $pdfPath;
    //             $claim->save();
    
    //             return response()->json([
    //                 'message' => 'Claim created successfully, PDF generated and stored.',
    //                 'claim' => $claim
    //             ], 201);
    
    //         } catch (\Exception $e) {
    //             \Log::error('Error generating PDF: ' . $e->getMessage());
    //             return response()->json(['error' => 'Failed to generate PDF.'], 500);
    //         }
    //     }
    
    //     return response()->json([
    //         'message' => 'Claim created successfully.',
    //         'claim' => $claim
    //     ], 201);
    // }


    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'admin_id' => 'required|exists:admins,id',
            'claim' => 'required|string',
            'hearing_invitation_id' => 'required|exists:hearing_invitations,id',
        ]);

        // Extract the request parameters
        $workerId = $request->worker_id;
        $adminId = $request->admin_id;
        $claimText = $request->claim;
        $hearingInvitationId = $request->hearing_invitation_id;

        // Create the claim record
        $claim = Claim::create([
            'worker_id' => $workerId,
            'admin_id' => $adminId,
            'claim' => $claimText,
            'hearing_invitation_id' => $hearingInvitationId,
        ]);

        // Log claim creation for debugging
        \Log::info('Claim created', ['claim_id' => $claim->id]);

        // Fetch the hearing invitation directly using the passed hearing_invitation_id
        $hearingInvitation = HearingInvitation::find($hearingInvitationId);

        if ($hearingInvitation) {
            \Log::info('Hearing Invitation found', [
                'hearing_invitation_id' => $hearingInvitation->id,
                'worker_id' => $hearingInvitation->user_id,
                'admin_id' => $hearingInvitation->team_id,
            ]);
        } else {
            \Log::info('Hearing Invitation not found', [
                'worker_id' => $workerId,
                'admin_id' => $adminId,
            ]);
        }

        if ($hearingInvitation) {
            try {
                // Fetch the necessary models for worker and admin
                $worker = User::findOrFail($hearingInvitation->user_id);
                $admin = Admin::findOrFail($hearingInvitation->team_id);

                // Log worker and admin information for debugging
                \Log::info('Worker and Admin details fetched', [
                    'worker_name' => $worker->firstname,
                    'admin_name' => $admin->name
                ]);

                // Prepare data for the PDF
                $companyName = $worker->company_type ?? 'Company Name';
                $employeeName = $worker->firstname;
                $position = $worker->role;
                $transactionStartDate = $worker->created_at;
                $hearingDate = $hearingInvitation->start_date;
                $hearingTime = $hearingInvitation->start_time;
                $employerReasons = $hearingInvitation->purpose; // Single reason
                $employeeClaims = $hearingInvitation->employee_claims; // Single claim
                $officerName = $admin->name;
                $officerPosition = $admin->role;

                // Build the HTML content for the PDF
                $html = "<html lang='en'>
                        <head>
                            <meta charset='UTF-8'>
                            <title>Minutes of the Hearing</title>
                            <style>
                                body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; }
                                .header { text-align: center; margin-bottom: 20px; }
                                .section { margin-bottom: 20px; }
                                .signature { margin-top: 40px; }
                            </style>
                        </head>
                        <body>
                            <div class='header'>
                                <h2>Minutes of the Hearing</h2>
                            </div>
                            <div class='section'>
                                <p><strong>Company Name:</strong> {$companyName}</p>
                                <p><strong>Name of the Employee:</strong> {$employeeName}</p>
                                <p><strong>Position in the Company:</strong> {$position}</p>
                                <p><strong>Transaction Start Date:</strong> {$transactionStartDate}</p>
                                <p><strong>The Hearing was held on:</strong> {$hearingDate} at {$hearingTime}</p>
                            </div>
                            <div class='section'>
                                <h4>The Employer's Reasons for Holding the Hearing:</h4>
                                <p>{$employerReasons}</p> <!-- Single reason -->
                            </div>
                            <div class='section'>
                                <h4>Claims of the Employee:</h4>
                                <p>{$employeeClaims}</p> <!-- Single claim -->
                            </div>
                            <div class='signature'>
                                <p><strong>Signature of the Hearing Officer:</strong> {$officerName}</p>
                                <p><strong>Position in the Company:</strong> {$officerPosition}</p>
                            </div>
                            <p>A copy of this protocol will be given to the employee</p>
                        </body>
                        </html>";

                $options = new Options();
                $options->set('isHtml5ParserEnabled', true);
                $dompdf = new Dompdf($options);

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                $pdfContent = $dompdf->output();

                $pdfFilename = 'hearing_minutes_' . $claim->id . '.pdf';
                $pdfPath = 'public/app/claim/' . $pdfFilename;

                // Log the PDF path
                \Log::info('Generated PDF content', ['pdf_path' => $pdfPath]);

                Storage::disk('public')->put($pdfPath, $pdfContent);

                if (Storage::disk('public')->exists($pdfPath)) {
                    \Log::info('PDF successfully stored at: ' . $pdfPath);
                } else {
                    \Log::error('Failed to store PDF at: ' . $pdfPath);
                }

                $claim->file = 'storage/' . $pdfPath;
                $claim->save();

                return response()->json([
                    'message' => 'Claim created successfully, PDF generated and stored.',
                    'claim' => $claim
                ], 201);

            } catch (\Exception $e) {
                \Log::error('Error generating PDF: ' . $e->getMessage());
                return response()->json(['error' => 'Failed to generate PDF.'], 500);
            }
        }

        return response()->json([
            'message' => 'Claim created successfully.',
            'claim' => $claim
        ], 201);
    }

    // Retrieve claims for a specific worker
    public function showWorkerClaims($workerId)
    {
        $claims = Claim::where('worker_id', $workerId)->get();

        return response()->json([
            'claims' => $claims
        ]);
    }

    // Retrieve claims for a specific admin
    public function showAdminClaims($adminId)
    {
        $claims = Claim::where('admin_id', $adminId)->get();

        return response()->json([
            'claims' => $claims
        ]);
    }
}
