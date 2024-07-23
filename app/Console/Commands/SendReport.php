<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\WorkerInvitation;
use App\Models\Form;
use League\Csv\Writer;
use SplTempFileObject;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;

class SendReport extends Command
{
    // The name and signature of the console command.
    protected $signature = 'report';

    // The console command description.
    protected $description = 'Display and export all user data';

    // Execute the console command.
    public function handle()
    {
        // Get all worker invitations
        $workerInvitations = WorkerInvitation::all();

        // Initialize an empty collection to store matching users
        $matchingUsers = collect();

        // Iterate over worker invitations to find matching users
        foreach ($workerInvitations as $invitation) {
            $user = User::where('email', $invitation->email)
                        ->orWhere('phone', $invitation->phone)
                        ->first();
                             

            $user_created = $user ? true : false;
            
            $is_submitted = $user && Form::where('user_id', $user->id)
                                        ->whereNotNull('submitted_at')
                                        ->exists() ? true : false;
          
            $manpower_company = $invitation->manpower_company_name;
            if ($user_created) {

            if (!empty($manpower_company)) {
                $profile_completed = true;
                $form101 = false;
                $contract = false;
                $safety_and_gear = false;
            } else if ($is_submitted) {
                $form101 = Form::where('user_id', $user->id)->where('type', 'form101')->whereNotNull('submitted_at')->exists() ? true : false;
                $contract = Form::where('user_id', $user->id)->where('type', 'contract')->whereNotNull('submitted_at')->exists() ? true : false;
                $safety_and_gear = Form::where('user_id', $user->id)->where('type', 'saftey-and-gear')->whereNotNull('submitted_at')->exists() ? true : false;
                $profile_completed = $form101 && $contract && $safety_and_gear;
            } else {
                $form101 = false;
                $contract = false;
                $safety_and_gear = false;
                $profile_completed = false;
            }
        }else{
            $form101 = false;
            $contract = false;
            $safety_and_gear = false;
            $profile_completed = false;
        }
        

     
            $matchingUsers->push([
                'id' => $invitation->id,
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'phone' => $invitation->phone,
                'email' => $invitation->email,
                'company' => $invitation->company,
                'manpower_company_name' => $invitation->manpower_company_name ? $invitation->manpower_company_name : 'Null',
                'form101' => $form101 ? 'True' : 'False',
                'contract' => $contract ? 'True' : 'False',
                'safety_and_gear' => $safety_and_gear ? 'True' : 'False',
                'user_created' => $user_created ? 'True' : 'False',
                'profile_completed' => $profile_completed ? 'True' : 'False',
            ]);
        }

        if ($matchingUsers->isEmpty()) {
            $this->info('No matching users found.');
            return;
        }

        $headers = [
            'ID', 'First Name', 'Last Name', 'Phone', 'Email', 'Company', 'Manpower Company Name', 'Form101', 'Contract', 'Safety_and_Gear', 'user_created', 'profile_completed'
        ];

        // Create CSV writer instance
        $csv = Writer::createFromFileObject(new SplTempFileObject());

        // Insert the headers
        $csv->insertOne($headers);

        // Insert the rows
        foreach ($matchingUsers as $row) {
            $csv->insertOne($row);
        }

        // Output the CSV file to storage
        $fileName = 'users_report.csv';
        Storage::put($fileName, $csv->toString());

        $this->info("Report has been exported to storage/{$fileName}");

        // Send the email with the CSV attachment
        Mail::to(config("mail.mailers.smtp.username"))->send(new class($fileName) extends Mailable
        {
            public $fileName;

            public function __construct($fileName)
            {
                $this->fileName = $fileName;
            }

            public function build()
            {
                $filePath = Storage::path($this->fileName);

                return $this->view('Mails.report')
                            ->subject('User Data Report')
                            ->attach($filePath, [
                                'as' => $this->fileName,
                                'mime' => 'text/csv',
                            ])
                            ->with([
                                'fileName' => $this->fileName, // Pass the filename to the view
                            ]);
            }
        });

        $this->info("Report has been sent to recipient@example.com");
    }
}
