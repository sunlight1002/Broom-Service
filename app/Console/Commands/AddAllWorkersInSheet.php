<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleSheetsService;
use App\Services\GoogleSheetsJobSyncService;
use App\Models\User;
use App\Models\Job;
use Carbon\Carbon;

class AddAllWorkersInSheet extends Command
{

    protected $hebrewMonths = [
        1 => 'ינואר',
        2 => 'פברואר',
        3 => 'מרץ',
        4 => 'אפריל',
        5 => 'מאי',
        6 => 'יוני',
        7 => 'יולי',
        8 => 'אוגוסט',
        9 => 'ספטמבר',
        10 => 'אוקטובר',
        11 => 'נובמבר',
        12 => 'דצמבר',
    ];
    protected $hebrewWeekdays = [
        'Sunday'    => 'ראשון',
        'Monday'    => 'שני',
        'Tuesday'   => 'שלישי',
        'Wednesday' => 'רביעי',
        'Thursday'  => 'חמישי',
        'Friday'    => 'שישי',
        'Saturday'  => 'שבת',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workers:add-to-sheet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all workers to Google Sheets';

    /**
     * The Google Sheets service instance.
     */
    protected $sheetsService;
    protected $spreadsheetId;
    protected $GoogleSheetsJobSyncService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        parent::__construct();
        $this->sheetsService = $googleSheetsService;
        $this->GoogleSheetsJobSyncService = new GoogleSheetsJobSyncService($googleSheetsService);
        $this->spreadsheetId = $googleSheetsService->spreadsheetId;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Fetching workers data...');
        $date = "17-03-2025"; // Given date
        $currentDate = Carbon::createFromFormat('d-m-Y', $date);
        
        // Get all workers (latest first)
        $workers = User::orderBy('created_at', 'desc')->get();

        if ($workers->isEmpty()) {
            $this->error('No workers found.');
            return 1;
        }

        $data = [];

        foreach ($workers as $worker) {
            // Get worker availabilities for the date
            $availabilities = $worker->availabilities()
                ->where('date', $currentDate->format('Y-m-d'))
                ->orderBy('start_time')
                ->get();

            // Check if a job exists on the given date
            $job = Job::where('worker_id', $worker->id)->where('start_date', $currentDate->format('Y-m-d'))->first();
            $jobExist = !is_null($job);
            $jobStartTime = $jobExist ? $job->start_time : null;
            $jobEndTime = $jobExist ? $job->end_time : null;

            if ($availabilities->isEmpty()) {
                continue; // Skip worker if no availability found or job exists
            }

            $shiftsAdded = []; // To store unique shifts per worker

            
            foreach ($availabilities as $availability) {
                $shifts = $this->identifyShift(
                    $availability->start_time, 
                    $availability->end_time,
                    $jobExist, 
                    $jobStartTime, 
                    $jobEndTime
                );

                foreach ($shifts as $shift) {
                    if (!in_array($shift, $shiftsAdded)) {
                        $shiftsAdded[] = $shift; // Store added shift
                        $data[] = [
                            $worker->firstname . ' ' . $worker->lastname,
                            $shift
                        ];
                    }
                }
            }
        }

        if (empty($data)) {
            $this->info('No workers to add.');
            return 1;
        }
        // \Log::info(['data' => $data]);

        $this->info('Preparing data for Google Sheets...');

        // Send data to Google Sheets
        $sheetProps = $this->GoogleSheetsJobSyncService->getOrCreateMonthlySheet(Carbon::parse($date));
        $sheetTitle = $sheetProps->getTitle();
        $sheetId    = $sheetProps->getSheetId();

        // Build the target date string (e.g., "יום ראשון 02.02")
        $targetDateString = $this->GoogleSheetsJobSyncService->formatDateRow(Carbon::parse($date));
        $sheetData = $this->sheetsService->getSheetData($sheetTitle, 'A:X');

        // Find or insert the date block for the target date.
        $dateRowIndex = $this->findDateRowIndex($sheetTitle, $targetDateString, $sheetData);
        \Log::info("dateRowIndex: " . $dateRowIndex);

        $targetRow = $dateRowIndex + 1; // Ensure correct indexing
        \Log::info("Final target row: " . $targetRow);

        $range = "{$sheetTitle}!J2{$targetRow}:K{$targetRow}";
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => $data,
        ]);
        $this->sheetsService->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );
        $jobRowIndex0 = $targetRow - 1;
        $workerOptions = $this->getWorkerDropdownOptions();

        foreach ($data as $row) {
            $this->sheetsService->setDropdownValidation($sheetId, $jobRowIndex0, $jobRowIndex0 + 1, 9, 10, $workerOptions);
            $jobRowIndex0++;
        }

        $this->info('Workers added successfully.');

        return 0;
    }

    public function findDateRowIndex($sheetTitle, $targetDateString, $sheetData)
    {

        $data = $sheetData;
    
        $firstDateIndex = null;
        $currentDate = null;
        $searching = false;
    
        foreach ($data as $index => $row) {
            if (isset($row[3]) && strpos($row[3], "יום ") === 0) {
                $currentDate = trim($row[3]);
            }
    
            // Start searching when the target date appears
            if ($currentDate == $targetDateString) {
                if ($firstDateIndex === null) {
                    $firstDateIndex = $index + 1;
                    $searching = true; // Start checking rows after finding the date
                }
            }
        }
    
        // If worker wasn't found, return the next row after the first date found
        return $firstDateIndex; // Remove "+1" if unnecessary
    }

    public function identifyShift($startTime, $endTime, $jobExist = null, $jobStartTime = null, $jobEndTime = null)
    {
        if (!$startTime || !$endTime) {
            return [];
        }
    
        $startHour = date('H', strtotime($startTime));
        $endHour = date('H', strtotime($endTime));
    
        // Treat midnight (00:00) as 24 for proper comparisons
        if ($endHour == 0) {
            $endHour = 24;
        }
    
        \Log::info("Start Hour: $startHour, End Hour: $endHour");

        $assignedShifts = [];
    
        // Define three fixed shifts
        $shiftPeriods = [
            "בוקר" => [8, 12],    // Morning: 08:00 - 11:59
            "צהריים" => [12, 15],  // Noon: 12:00 - 14:59
            "אחר הצהריים" => [15, 24], // Afternoon: 15:00 - 23:59
        ];
    
        // If a job exists, convert job start/end times to integers
        $jobStartHour = $jobStartTime ? date('H', strtotime($jobStartTime)) : null;
        $jobEndHour = $jobEndTime ? date('H', strtotime($jobEndTime)) : null;

        foreach ($shiftPeriods as $shiftName => [$shiftStart, $shiftEnd]) {
            // Check if worker availability overlaps with this shift
            $workerCoversShift = ($startHour < $shiftEnd && $endHour > $shiftStart);
    
            // Check if this shift conflicts with the job hours
            $conflictsWithJob = $jobExist && ($jobStartHour < $shiftEnd && $jobEndHour > $shiftStart);
    
            if ($workerCoversShift && !$conflictsWithJob) {
                $assignedShifts[] = $shiftName;
            }
        }
    
        return array_unique($assignedShifts);
    }
    
    

    protected function getWorkerDropdownOptions()
    {
        return User::where('status', '!=' , 0)->get()->pluck('fullname')->toArray();
    }

}


// $values = [
//     ["Worker ID"], // This will go into column J
//     ["12345"],
//     ["67890"]
// ];

// $body = new Google_Service_Sheets_ValueRange([
//     'values' => $values
// ]);

// $params = [
//     'valueInputOption' => 'RAW',
//     'insertDataOption' => 'INSERT_ROWS' // Ensures data is appended, not overwritten
// ];

// $spreadsheetId = 'your-spreadsheet-id';
// $range = 'Sheet1!J:J'; // Appends only in column J

// $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
