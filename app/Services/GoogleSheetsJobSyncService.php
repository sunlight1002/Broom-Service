<?php

namespace App\Services;

use Carbon\Carbon;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\ValueRange;
use App\Models\User;

class GoogleSheetsJobSyncService
{
    protected $sheetsService;
    protected $spreadsheetId;
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

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->sheetsService = $googleSheetsService;
        $this->spreadsheetId = $googleSheetsService->spreadsheetId;
    }

    /**
     * Returns an existing monthly sheet (by Hebrew month name + year) or creates a new one with header row.
     */
    public function getOrCreateMonthlySheet(Carbon $date)
    {
        $month = $date->month;
        $sheetTitle = $this->hebrewMonths[$month]; // e.g. "ינואר"

        // Retrieve spreadsheet properties to see if the sheet exists.
        $spreadsheet = $this->sheetsService->service->spreadsheets->get($this->sheetsService->spreadsheetId);
        $sheets = $spreadsheet->getSheets();

        foreach ($sheets as $sheet) {
            $props = $sheet->getProperties();
            if ($props->getTitle() == $sheetTitle) {
                return $props;
            }
        }

        // Sheet not found; create a new one with RTL enabled.
        $addSheetRequest = new \Google\Service\Sheets\Request([
            'addSheet' => [
                'properties' => [
                    'title' => $sheetTitle,
                    'gridProperties' => [
                        'rowCount' => 1000,
                        'columnCount' => 23, // Maximum columns A-W
                    ],
                    'rightToLeft' => true, // Set the sheet layout to RTL.
                ],
            ],
        ]);
        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [$addSheetRequest],
        ]);
        $response = $this->sheetsService->service->spreadsheets->batchUpdate(
            $this->sheetsService->spreadsheetId,
            $batchUpdateRequest
        );
        $newSheetProps = $response->getReplies()[0]->getAddSheet()->getProperties();

        // Build header row.
        $headers = [
            "שם לקוח",
            "מזהה לקוח",
            "מזהה הצעה",
            "תעריף (אלכס)",
            "יכל",
            "שיבוץ",
            "אלכס",
            "",
            "עובדים",
            "",
            "שעות עבודה",
            "חבילת לקוח",
            "",
            "שעות לביצוע",
            "שעות בפועל",
            "הערות",
            "תדירות",
            "כתובת",
            "",
            "כתובות לקוח",
            "מזהה משימה ב-CRM",
            "קישור למשימה ב-CRM",
            "מספר הזמנה"
        ];
        $headerColumnCount = count($headers); // e.g. 23 columns

        // Define header range as A1:W1.
        $headerRange = "{$sheetTitle}!A1:W1";
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [$headers],
        ]);
        $this->sheetsService->service->spreadsheets_values->update(
            $this->sheetsService->spreadsheetId,
            $headerRange,
            $body,
            ['valueInputOption' => 'RAW']
        );

        // Prepare header formatting requests.
        $headerSheetId = $newSheetProps->getSheetId();
        $requests = [
            // 1. Freeze the header row.
            new \Google\Service\Sheets\Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $headerSheetId,
                        'gridProperties' => ['frozenRowCount' => 1],
                    ],
                    'fields' => 'gridProperties.frozenRowCount',
                ],
            ]),
            // 2. Set header text to bold.
            new \Google\Service\Sheets\Request([
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $headerSheetId,
                        'startRowIndex' => 0,   // first row (0-indexed)
                        'endRowIndex' => 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => $headerColumnCount,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => ['bold' => true],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat.bold',
                ],
            ]),
            // 3. Set header background color to light grey.
            new \Google\Service\Sheets\Request([
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $headerSheetId,
                        'startRowIndex' => 0,
                        'endRowIndex' => 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => $headerColumnCount,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor' => [
                                'red' => 0.9,
                                'green' => 0.9,
                                'blue' => 0.9,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.backgroundColor',
                ],
            ]),
            // 4. Clear background color for rows 2 to 1000 (for the header columns).
            new \Google\Service\Sheets\Request([
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $headerSheetId,
                        'startRowIndex' => 1, // row 2 onward (0-indexed)
                        'endRowIndex' => 1000, // adjust if needed
                        'startColumnIndex' => 0,
                        'endColumnIndex' => $headerColumnCount,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor' => [
                                'red' => 1,
                                'green' => 1,
                                'blue' => 1,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.backgroundColor',
                ],
            ]),
        ];
        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ]);
        $this->batchUpdateWithBackoff(
            $this->sheetsService->spreadsheetId,
            $batchUpdateRequest
        );

        return $newSheetProps;
    }

    protected function batchUpdateWithBackoff($spreadsheetId, $batchUpdateRequest, $maxRetries = 5)
    {
        $delay = 1; // initial delay in seconds
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                return $this->sheetsService->service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
            } catch (\Google\Service\Exception $ex) {
                if ($ex->getCode() == 429) {
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }
                throw $ex;
            }
        }
        throw new \Exception("Exceeded maximum retries for batch update");
    }



    /**
     * Format a date row string in Hebrew.
     * For example: "יום ראשון 02.02"
     */
    public function formatDateRow(Carbon $date)
    {
        $weekdayEng = $date->format('l');
        $hebrewWeekday = $this->hebrewWeekdays[$weekdayEng] ?? $weekdayEng;
        return "יום " . $hebrewWeekday . " " . $date->format('d.m');
    }

    /**
     * Searches for a date row (in column D) that matches the target date string.
     * Returns the row number (1-indexed) if found or null.
     */
    public function findDateRowIndex($sheetTitle, $targetDateString)
    {
        $data = $this->sheetsService->getSheetData($sheetTitle, 'A:W');
        // Skip header row (row 1)
        foreach ($data as $index => $row) {
            // Column D is index 3.
            if (isset($row[3]) && trim($row[3]) == $targetDateString) {
                return $index + 1;
            }
        }
        return null;
    }

    /**
     * Searches for a job row by CRM job id (assumed to be in column U, index 20).
     * Returns the row number (1-indexed) if found or null.
     */
    public function findJobRowIndex($sheetTitle, $crmJobId)
    {
        $data = $this->sheetsService->getSheetData($sheetTitle, 'A:W');
        // Start from row 2 (skip header) – note: date blocks and blank rows may be present.
        foreach ($data as $index => $row) {
            // Check column U (index 20)
            if (isset($row[20]) && $row[20] == $crmJobId) {
                return $index + 1;
            }
        }
        return null;
    }

    /**
     * Delete a row at the given 1-indexed row number from the sheet.
     */
    public function deleteRow($sheetId, $rowNumber)
    {
        // API requires 0-indexed indices.
        $requests = [
            new Request([
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $rowNumber - 1,
                        'endIndex' => $rowNumber,
                    ],
                ],
            ]),
        ];
        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ]);
        return $this->sheetsService->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }

    /**
     * Inserts a new date block (11 rows: 5 blank above, 1 date row, 5 blank below)
     * at the specified insertion point.
     * Returns the row number (1-indexed) of the inserted date row.
     */
    public function insertDateBlock($sheetTitle, $insertRowIndex, $dateString, $sheetId)
    {
        $numRows = 11;
        // Insert rows at the desired index (API uses 0-indexed rows).
        $requests = [
            new Request([
                'insertDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $insertRowIndex - 1,
                        'endIndex' => $insertRowIndex - 1 + $numRows,
                    ],
                    'inheritFromBefore' => false,
                ],
            ]),
        ];
        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ]);
        $this->batchUpdateWithBackoff($this->spreadsheetId, $batchUpdateRequest);

        // The date row is the 6th row of the inserted block (0-indexed).
        $dateRowIndex = $insertRowIndex + 5 - 1;
        $range = "{$sheetTitle}!D" . ($dateRowIndex + 1);
        $body = new ValueRange([
            'values' => [[$dateString]],
        ]);
        $this->sheetsService->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );

        // Apply green background formatting to the date row (columns A-W).
        $greenColor = ['red' => 0.56, 'green' => 0.96, 'blue' => 0.56];
        $repeatCellRequest = new Request([
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $dateRowIndex,
                    'endRowIndex' => $dateRowIndex + 1,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 23,
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'backgroundColor' => $greenColor,
                    ],
                ],
                'fields' => 'userEnteredFormat.backgroundColor',
            ],
        ]);
        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
            'requests' => [$repeatCellRequest],
        ]);
        $this->batchUpdateWithBackoff($this->spreadsheetId, $batchUpdateRequest);

        return $dateRowIndex + 1;
    }

    /**
     * Generate an array representing a job row.
     * Adjust the field mapping as needed.
     */
    public function generateJobRow($job)
    {
        $clientName      = "";
        if (!empty($job->client->invoicename)) {
            $clientName = $job->client->invoicename;
        } else {
            $clientName = $job->client->firstname . ' ' . $job->client->lastname;
        }
        $clientId        = "#" . $job->client_id;
        $offerId         = $job->offer_id;
        $jobAmount       = $job->subtotal_amount; // amount without tax
        $paymentStatus   = $job->is_paid ? "שולם" : "לא שולם";
        $notCancelled    = ($job->status != 'cancelled') ? true : false;
        $jobCompleted    = ($job->is_job_done || $job->completed_at) ? true : false;
        $blank           = "";
        $workerName      = $job->worker->fullname ?? "";
        $jobWorker       = $workerName;
        $shift           = $this->determineShift($job->start_time);
        $serviceName     = $job->offer_service->name ?? "";
        if ($job->offer_service) {
            if ($job->offer_service['template'] == 'airbnb') {
                $serviceName = $job->offer_service['name'];
                if (isset($job->offer_service['sub_services']['sub_service_name'])) {
                    $serviceName .=  (' (' . $job->offer_service['sub_services']['sub_service_name'] . ')');
                }
            } else {
                $serviceName = $job->offer_service['name'];
            }
        }
        $jobService      = $serviceName;
        // Calculate planned hours from start_time and end_time.
        $plannedHours = 0;
        if ($job->start_time && $job->end_time) {
            $plannedHours = \Carbon\Carbon::parse($job->end_time)
                ->floatDiffInHours(\Carbon\Carbon::parse($job->start_time));
        }
        $actualHours     = isset($job->actual_time_taken_minutes) ? round($job->actual_time_taken_minutes / 60, 2) : "";
        $jobComment = $job->comments->implode('comment', ' | ');
        $frequency       = '';
        if ($job->offer_service && $job->offer_service['freq_name']) {
            $frequency = $job->offer_service['freq_name'];
        }
        $jobAddress      = "";
        if ($job->offer_service) {
            if ($job->offer_service['template'] == 'airbnb') {
                if (isset($job->offer_service['sub_services']['address'])) {
                    $address = $job->client->property_addresses()->where('id', $job->offer_service['sub_services']['address'])->first();
                    if ($address) {
                        $jobAddress = $address->address_name;
                    }
                } else {
                    $address = $job->client->property_addresses()->where('id', $job->offer_service['address'])->first();
                    if ($address) {
                        $jobAddress = $address->address_name;
                    }
                }
            } else {
                $address = $job->client->property_addresses()->where('id', $job->offer_service['address'])->first();
                if ($address) {
                    $jobAddress = $address->address_name;
                }
            }
        }
        $clientAddresses = $jobAddress;
        $crmJobId        = $job->id;
        $crmJobLink      = "https://crm.broomservice.co.il/admin/jobs/view/" . $job->id;
        $orderId         = ($job->order) ? ("https://app.icount.co.il/hash/show_doc.php?doctype=order&docnum=" . $job->order->order_id) : "";

        return [
            $clientName,
            $clientId,
            $offerId,
            $jobAmount,
            $paymentStatus,
            $notCancelled,
            $jobCompleted,
            $blank,
            $workerName,
            $jobWorker,
            $shift,
            $serviceName,
            $jobService,
            $plannedHours,
            $actualHours,
            $jobComment,
            $frequency,
            $jobAddress,
            $blank,
            $clientAddresses,
            $crmJobId,
            $crmJobLink,
            $orderId,
        ];
    }

    /**
     * Determine the shift name (in Hebrew) based on start_time.
     */
    public function determineShift($startTime)
    {
        if (!$startTime) {
            return "";
        }
        $time = strtotime($startTime);
        $hour = date('H', $time);
        if ($hour >= 8 && $hour < 12) {
            return "בוקר";
        } elseif ($hour >= 12 && $hour < 15) {
            return "צהריים";
        } elseif ($hour >= 15 && $hour < 19) {
            return "אחר הצהריים";
        } elseif ($hour >= 19 && $hour < 23) {
            return "ערב";
        } else {
            return "לילה";
        }
    }

    /**
     * Helper: Get dynamic worker dropdown options.
     * In production, fetch these from your database or configuration.
     */
    protected function getWorkerDropdownOptions()
    {
        return User::where('status', 1)->get()->pluck('fullname')->toArray();
    }

    /**
     * Helper: Get dynamic service dropdown options.
     */
    protected function getServiceDropdownOptions($offerData)
    {
        $services = [];
        $data = json_decode($offerData, true);
        foreach ($data as $d) {
            $services[] = $d['name'];
            if ($d['template'] == 'airbnb') {
                $s =  $d['name'];
                if (isset($d['sub_services']['sub_service_name'])) {
                    $s .= (' (' . $d['sub_services']['sub_service_name'] . ')');
                }
                $services[] = $s;
            } else {
                $services[] = $data[0]['name'];
            }
        }
        return $services;
    }

    /**
     * Helper: Get dynamic frequency dropdown options.
     */
    protected function getFrequencyDropdownOptions($offerData)
    {
        $data = json_decode($offerData, true);
        $frequencies = [];
        foreach ($data as $d) {
            $frequencies[] = $d['freq_name'];
        }
        return $frequencies;
    }

    /**
     * Helper: Get dynamic client address dropdown options based on client id.
     */
    protected function getClientAddressDropdownOptions($client)
    {
        if (!$client) {
            return [];
        }
        return $client->property_addresses->pluck('address_name')->toArray();
    }

    /**
     * Sync a job record into the appropriate monthly sheet.
     * If the job (by CRM id) already exists, it is removed first.
     * If the job's date has changed, it is reinserted under the proper date block.
     */
    /**
     * Sync a job record into the appropriate monthly sheet.
     * If the job (by CRM id) already exists, it is removed first.
     * If the job's date has changed, it is reinserted under the proper date block.
     */
    public function syncJob($job)
    {
        // Determine the job's date.
        $jobDate = $job->start_date ? new Carbon($job->start_date) : new Carbon($job->created_at);
        $sheetProps = $this->getOrCreateMonthlySheet($jobDate);
        $sheetTitle = $sheetProps->getTitle();
        $sheetId    = $sheetProps->getSheetId();

        // Build the target date string (e.g., "יום ראשון 02.02")
        $targetDateString = $this->formatDateRow($jobDate);

        // Check if the job record (by CRM id) already exists in this sheet.
        $existingJobRow = $this->findJobRowIndex($sheetTitle, $job->id);
        if ($existingJobRow !== null) {
            // Update scenario: update only specific columns.
            $updateRanges = [
                'D' => 3,
                'E' => 4,
                'F' => 5,
                'G' => 6,
                'I' => 8,
                'J' => 9,
                'L' => 11,
                'M' => 12,
                'N' => 13,
                'T' => 19,
                'W' => 22,
            ];
            $updates = [];
            $fullRow = $this->generateJobRow($job);
            $fullRow = array_values($fullRow);
            foreach ($updateRanges as $colLetter => $colIndex) {
                $range = "{$sheetTitle}!{$colLetter}{$existingJobRow}:{$colLetter}{$existingJobRow}";
                $updates[] = new \Google\Service\Sheets\ValueRange([
                    'range'  => $range,
                    'values' => [[$fullRow[$colIndex]]],
                ]);
            }
            $batchUpdateValuesRequest = new \Google\Service\Sheets\BatchUpdateValuesRequest([
                'data' => $updates,
                'valueInputOption' => 'RAW',
            ]);
            $this->sheetsService->service->spreadsheets_values
                ->batchUpdate($this->spreadsheetId, $batchUpdateValuesRequest);
            return;
        }

        // --------------------------------------------------------
        // Determine the correct insertion index for the date block (ascending order).
        // --------------------------------------------------------
        // Fetch all sheet data (range A:W).
        $sheetData = $this->sheetsService->getSheetData($sheetTitle, 'A:W');
        // Use count($sheetData) to determine default insertion (append at end).
        $defaultInsertRowIndex = count($sheetData) + 1; // 1-indexed

        // Collect existing date rows from column D (index 3).
        // We expect date rows to start with "יום " and follow the format "יום <hebrewWeekday> dd.mm"
        $existingDateRows = [];
        foreach ($sheetData as $index => $row) {
            if (isset($row[3]) && strpos($row[3], "יום ") === 0) {
                $parts = explode(" ", $row[3]);
                if (count($parts) >= 3) {
                    $datePart = $parts[2]; // e.g. "02.02"
                    try {
                        // Use the job's year for proper comparison.
                        $existingDate = Carbon::createFromFormat('d.m.Y', $datePart . '.' . $jobDate->year);
                        $existingDateRows[] = [
                            'rowIndex' => $index + 1, // convert 0-index to 1-index
                            'date'     => $existingDate,
                        ];
                    } catch (\Exception $ex) {
                        // Skip rows with parse errors.
                    }
                }
            }
        }
        // Determine insertion index: insert before the first date row that is later than our job date.
        $insertRowIndex = $defaultInsertRowIndex;
        foreach ($existingDateRows as $dateRow) {
            if ($jobDate->lessThan($dateRow['date'])) {
                $insertRowIndex = $dateRow['rowIndex'];
                break;
            }
        }
        // --------------------------------------------------------

        // Ensure the grid is large enough.
        $spreadsheetUpdated = $this->sheetsService->service->spreadsheets->get($this->sheetsService->spreadsheetId);
        $currentRowCount = null;
        foreach ($spreadsheetUpdated->getSheets() as $sheet) {
            if ($sheet->getProperties()->getSheetId() == $sheetId) {
                $currentRowCount = $sheet->getProperties()->getGridProperties()->getRowCount();
                break;
            }
        }
        if ($currentRowCount === null) {
            throw new \Exception("Could not fetch current row count for sheetId: $sheetId");
        }
        if ($insertRowIndex > $currentRowCount) {
            $newRowCount = max($currentRowCount * 2, $insertRowIndex);
            $updateRequest = new \Google\Service\Sheets\Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $sheetId,
                        'gridProperties' => ['rowCount' => $newRowCount],
                    ],
                    'fields' => 'gridProperties.rowCount',
                ],
            ]);
            $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [$updateRequest],
            ]);
            $this->batchUpdateWithBackoff($this->spreadsheetId, $batchUpdateRequest);
        }

        // Find or insert the date block for the target date.
        $dateRowIndex = $this->findDateRowIndex($sheetTitle, $targetDateString);
        if ($dateRowIndex === null) {
            $dateRowIndex = $this->insertDateBlock($sheetTitle, $insertRowIndex, $targetDateString, $sheetId);
        }

        // After inserting the date block, update grid size if needed.
        $spreadsheetUpdated = $this->sheetsService->service->spreadsheets->get($this->sheetsService->spreadsheetId);
        $currentRowCount = null;
        foreach ($spreadsheetUpdated->getSheets() as $sheet) {
            if ($sheet->getProperties()->getSheetId() == $sheetId) {
                $currentRowCount = $sheet->getProperties()->getGridProperties()->getRowCount();
                break;
            }
        }
        if ($currentRowCount === null) {
            throw new \Exception("Could not fetch current row count for sheetId: $sheetId");
        }
        if ($dateRowIndex >= $currentRowCount) {
            $newRowCount = max($currentRowCount * 2, $dateRowIndex + 1);
            $updateRequest = new \Google\Service\Sheets\Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $sheetId,
                        'gridProperties' => ['rowCount' => $newRowCount],
                    ],
                    'fields' => 'gridProperties.rowCount',
                ],
            ]);
            $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [$updateRequest],
            ]);
            $this->batchUpdateWithBackoff($this->spreadsheetId, $batchUpdateRequest);
        }

        // Insert a new job row immediately below the date row.
        $insertRequests = [
            new \Google\Service\Sheets\Request([
                'insertDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $dateRowIndex, // 0-indexed (dateRowIndex is 1-indexed)
                        'endIndex' => $dateRowIndex + 1,
                    ],
                    'inheritFromBefore' => false,
                ],
            ]),
        ];
        $batchInsertRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $insertRequests,
        ]);
        $this->batchUpdateWithBackoff($this->spreadsheetId, $batchInsertRequest);

        // Build and insert the job row.
        $jobRow = $this->generateJobRow($job);
        $jobRow = array_values($jobRow);
        $jobRow = array_map(function ($item) {
            return is_scalar($item) ? $item : (string)$item;
        }, $jobRow);
        $targetRow = $dateRowIndex + 1;
        $range = "{$sheetTitle}!A{$targetRow}:W{$targetRow}";
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [$jobRow],
        ]);
        $this->sheetsService->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );

        // Clear any inherited data validation in the new row.
        // Here we use repeatCell with an empty CellData object.
        $clearValidationCell = new \Google\Service\Sheets\CellData();
        $clearValidationRequest = new \Google\Service\Sheets\Request([
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $targetRow - 1,
                    'endRowIndex' => $targetRow,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 23,
                ],
                'cell' => $clearValidationCell,
                'fields' => 'dataValidation',
            ],
        ]);
        $clearValidationBatchRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [$clearValidationRequest],
        ]);
        $this->batchUpdateWithBackoff($this->spreadsheetId, $clearValidationBatchRequest);

        // Apply your own data validation rules.
        $jobRowIndex0 = $targetRow - 1;
        $this->sheetsService->setCheckbox($sheetId, $jobRowIndex0, $jobRowIndex0 + 1, 5, 6);
        $this->sheetsService->setCheckbox($sheetId, $jobRowIndex0, $jobRowIndex0 + 1, 6, 7);
        $workerOptions = $this->getWorkerDropdownOptions();
        $this->sheetsService->setDropdownValidation($sheetId, $jobRowIndex0, $jobRowIndex0 + 1, 9, 10, $workerOptions);
        $serviceOptions = $this->getServiceDropdownOptions($job->offer->services ?? []);
        $this->sheetsService->setDropdownValidation($sheetId, $jobRowIndex0, $jobRowIndex0 + 1, 12, 13, $serviceOptions);
        $frequencyOptions = $this->getFrequencyDropdownOptions($job->offer->services ?? []);
        $this->sheetsService->setDropdownValidation($sheetId, $jobRowIndex0, $jobRowIndex0 + 1, 16, 17, $frequencyOptions);
        $clientAddressOptions = $this->getClientAddressDropdownOptions($job->client);
        $this->sheetsService->setDropdownValidation($sheetId, $jobRowIndex0, $jobRowIndex0 + 1, 19, 20, $clientAddressOptions);
    }
}
