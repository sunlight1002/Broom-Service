<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;

class GoogleSheetsService
{
    public $service;
    public $spreadsheetId;
    protected $client;
    protected $googleRefreshToken;
    protected $googleAccessToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Laravel CRM Google Sheets Sync');
        $this->client->setScopes(Sheets::SPREADSHEETS);
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
    
        try {
            // Fetch tokens from DB
            $accessToken = Setting::where('key', SettingKeyEnum::GOOGLE_ACCESS_TOKEN)->value('value');
            $refreshToken = Setting::where('key', SettingKeyEnum::GOOGLE_REFRESH_TOKEN)->value('value');
    
            if (!$accessToken) {
                \Log::warning('Google access token not found.');
                return;
            }
    
            // Set token
            $token = ['access_token' => $accessToken];
            if ($refreshToken) {
                $token['refresh_token'] = $refreshToken;
            }
    
            $this->client->setAccessToken($token);
    
            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if (empty($refreshToken)) {
                    \Log::warning('Access token expired and no refresh token is available.');
                    return;
                }
    
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
    
                if (isset($newToken['error'])) {
                    \Log::error("Failed to refresh Google access token: {$newToken['error_description']}");
                    return;
                }
    
                if (isset($newToken['access_token'])) {
                    Setting::updateOrCreate(
                        ['key' => SettingKeyEnum::GOOGLE_ACCESS_TOKEN],
                        ['value' => $newToken['access_token']]
                    );
                }
    
                if (isset($newToken['refresh_token'])) {
                    Setting::updateOrCreate(
                        ['key' => SettingKeyEnum::GOOGLE_REFRESH_TOKEN],
                        ['value' => $newToken['refresh_token']]
                    );
                }
    
                $this->client->setAccessToken($newToken);
            }
    
            // Load Sheets service
            $this->service = new Sheets($this->client);
    
            $this->spreadsheetId = Setting::where('key', SettingKeyEnum::GOOGLE_SHEET_ID)->value('value');
    
            if (!$this->spreadsheetId) {
                \Log::warning('Google Spreadsheet ID not found.');
            }
    
        } catch (\Throwable $e) {
            \Log::error('Google Sheets Initialization Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    


    /**
     * Retrieve data from a given sheet and range.
     */
    public function getSheetData($sheetName, $range = 'A:Z')
    {
        $fullRange = "{$sheetName}!{$range}";
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $fullRange);
        return $response->getValues();
    }

    /**
     * Update a specific range with provided values.
     */
    public function updateSheetData($sheetName, $range, array $values)
    {
        $body = new ValueRange([
            'values' => $values,
        ]);
        $params = ['valueInputOption' => 'RAW'];
        return $this->service->spreadsheets_values->update($this->spreadsheetId, "{$sheetName}!{$range}", $body, $params);
    }

    /**
     * Append a new row to the given sheet.
     */
    public function appendSheetData($sheetName, array $values)
    {
        $body = new ValueRange([
            'values' => [$values],
        ]);
        $params = ['valueInputOption' => 'RAW'];
        return $this->service->spreadsheets_values->append($this->spreadsheetId, $sheetName, $body, $params);
    }

    /**
     * Set a dropdown (data validation) on a specified range.
     *
     * @param int   $sheetId          The numeric ID of the target sheet.
     * @param int   $startRowIndex    0-indexed start row.
     * @param int   $endRowIndex      0-indexed end row (exclusive).
     * @param int   $startColumnIndex 0-indexed start column.
     * @param int   $endColumnIndex   0-indexed end column (exclusive).
     * @param array $options          An array of option strings.
     *
     * @return \Google\Service\Sheets\BatchUpdateSpreadsheetResponse
     */
    public function setDropdownValidation($sheetId, $startRowIndex, $endRowIndex, $startColumnIndex, $endColumnIndex, array $options)
    {
        $values = [];
        foreach ($options as $option) {
            $values[] = ['userEnteredValue' => $option];
        }

        $request = new Request([
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $startRowIndex,
                    'endRowIndex' => $endRowIndex,
                    'startColumnIndex' => $startColumnIndex,
                    'endColumnIndex' => $endColumnIndex,
                ],
                'cell' => [
                    'dataValidation' => [
                        'condition' => [
                            'type' => 'ONE_OF_LIST',
                            'values' => $values,
                        ],
                        'showCustomUi' => true,
                    ],
                ],
                'fields' => 'dataValidation',
            ],
        ]);

        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
            'requests' => [$request],
        ]);

        return $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }

    public function setCheckbox($sheetId, $startRowIndex, $endRowIndex, $startColumnIndex, $endColumnIndex)
    {
        // Create a data validation rule for checkboxes.
        $rule = new \Google\Service\Sheets\DataValidationRule([
            'condition' => [
                'type' => 'BOOLEAN',
            ],
            'strict' => true,
            'showCustomUi' => true,
        ]);

        $request = new \Google\Service\Sheets\Request([
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $startRowIndex,
                    'endRowIndex' => $endRowIndex,
                    'startColumnIndex' => $startColumnIndex,
                    'endColumnIndex' => $endColumnIndex,
                ],
                'cell' => [
                    'dataValidation' => $rule,
                ],
                'fields' => 'dataValidation',
            ],
        ]);

        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [$request],
        ]);

        return $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }


    /**
     * Helper: Get the sheet ID for a given sheet title.
     *
     * @param string $sheetTitle
     * @return int|null
     */
    public function getSheetIdByTitle($sheetTitle)
    {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            $props = $sheet->getProperties();
            if ($props->getTitle() == $sheetTitle) {
                return $props->getSheetId();
            }
        }
        return null;
    }

    public function insertRowBefore($sheetId, $targetRow) {
        $requestBody = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [
                [
                    'insertDimension' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'dimension' => 'ROWS',
                            'startIndex' => $targetRow - 1, // Adjust for zero-based index
                            'endIndex' => $targetRow
                        ],
                        'inheritFromBefore' => false // Don't copy formatting
                    ]
                ]
            ]
        ]);
    
        $this->service->spreadsheets->batchUpdate(
            $this->spreadsheetId,
            $requestBody
        );
    }
    

    public function insertRow($sheetId, $rowIndex)
    {
        $requests = [
            new \Google\Service\Sheets\Request([
                'insertDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $rowIndex - 1, // Google Sheets API is zero-based
                        'endIndex' => $rowIndex
                    ],
                    'inheritFromBefore' => false
                ]
            ])
        ];

        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);

        $this->service->spreadsheets->batchUpdate(
            $this->spreadsheetId,
            $batchUpdateRequest
        );

        \Log::info("Inserted a new row at index $rowIndex in sheet ID $sheetId.");
    }

}
