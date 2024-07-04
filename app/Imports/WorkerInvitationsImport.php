<?php

namespace App\Imports;

use App\Models\WorkerInvitation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class WorkerInvitationsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if(!empty($row['phone'])) {
            // Clean the phone number by removing spaces and dashes
            $cleanedPhone = str_replace([' ', '-'], '', $row['phone']);
    
            // Parse and standardize the birth date
            $birthDate = $this->parseDate($row['bday']);
    
            // Capitalize first name and last name
            $firstName = ucfirst(strtolower($row['name']));
            $lastName = ucfirst(strtolower($row['last']));
            return new WorkerInvitation([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $row['email'],
                'phone' => $cleanedPhone,
                'birth_date' => $birthDate,
                'company' => $row['company'] === 'YES',
                'manpower_company_name' => $row['mp_name'],
                'form_101' => $row['101'] === 'YES',
                'contact' => $row['contract'] === 'YES',
                'safety' => $row['safty'] === 'YES',
                'insurance' => $row['insurance'] === 'YES',
                'country' => $row['country'],
                'visa_id' => $row['visaid'],
                'lng' => strtolower($row['leng']),
                'role' => $row['role'] ?? null,
                'payment' => $row['payment'] ?? null,
                'first_date' => $row['1st_day'] ? Carbon::createFromFormat('d.m.Y', $row['1st_day']) : null,
                'is_invitation_sent' => false // default value
            ]);
        }
        return;
    }

    /**
     * Parse and standardize the date.
     *
     * @param string $date
     * @return string|null
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // List of possible date formats
        $formats = [
            'd/m/Y', 'd.m.Y', 'd-M-Y', 'Y-m-d', 'm/d/Y'
        ];

        foreach ($formats as $format) {
            try {
                $parsedDate = Carbon::createFromFormat($format, $date);

                if ($parsedDate !== false) {
                    return $parsedDate->format('Y-m-d');
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        // If no formats matched, return null or log an error
        return null;
    }
}
