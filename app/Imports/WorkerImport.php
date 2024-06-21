<?php

namespace App\Imports;

use App\Models\Countries;
use App\Models\ManpowerCompany;
use App\Models\User;
use App\Traits\PaymentAPI;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkerImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use PaymentAPI;
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $languageOptions = [
            'Hebrew'    => 'heb',
            'English'   => 'en',
            'Russian'   => 'ru',
            'Spanish'   => 'spa'
        ];

        $statusOptions = [
            'Enable' => 1,
            'Disable' => 0,
        ];

        $genderOptions = [
            'Female'    => 'female',
            'Male'      => 'male',
        ];

        $companyTypeOptions = [
            'My Company' => 'my-company',
            'Manpower' => 'manpower'
        ];

        $countries = Countries::get(['id', 'code']);
        $manpowerCompanies = ManpowerCompany::get(['id', 'name']);

        $failedImports = collect([]);
        foreach ($collection as $row) {
            try {
                $validator = Validator::make($row->toArray(), [
                    'first_name' => ['required', 'string', 'max:255'],
                    'full_address'   => ['required', 'string'],
                    'phone'     => ['required'],
                    'worker_id' => ['required'],
                    'status'    => ['required'],
                    'password'  => ['required'],
                    'language'  => ['required'],
                    'email'     => ['nullable'],
                    'gender'    => ['required'],
                    'role'      => ['required', 'max:50'],
                ]);

                if ($validator->fails()) {
                    $failedImports->push($row);
                    continue;
                }

                if (!in_array($row['language'], array_keys($languageOptions))) {
                    throw new Exception('Invalid language');
                }

                if (!in_array($row['status'], array_keys($statusOptions))) {
                    throw new Exception('Invalid client status');
                }

                if (!in_array($row['gender'], array_keys($genderOptions))) {
                    throw new Exception('Invalid gender');
                }

                if (!in_array($row['company_type'], array_keys($companyTypeOptions))) {
                    throw new Exception('Invalid company type');
                }

                $country = $countries->where('code', $row['country'])->first();
                if (!$country) {
                    throw new Exception('Invalid country');
                }

                $manpowerCompanyID = NULL;
                if ($row['company_type'] == 'Manpower') {
                    $manpowerCompany = $manpowerCompanies->where('name', $row['manpower'])->first();
                    if (!$manpowerCompany) {
                        throw new Exception('Invalid manpower company');
                    }

                    $manpowerCompanyID = $manpowerCompany->id;
                }

                $workerData = [
                    'firstname' => $row['first_name'] ?? '',
                    'lastname'  => $row['last_name'] ?? '',
                    'phone'     => $row['phone'] ?? '',
                    'email'     => $row['email'] ?? '',
                    'role'      => $row['role'] ?? '',
                    'address'           => $row['full_address'] ?? '',
                    'payment_per_hour'  => $row['payment_per_hour'] ?? '',
                    'renewal_visa'      => date('Y-m-d', strtotime($row['renewal_of_visa'] ?? '')),
                    'worker_id'         => $row['worker_id'] ?? '',
                    'passcode'  => $row['password'] ?? '',
                    'password'  => Hash::make($row['password'] ?? ''),
                    'country'   => $country->name,
                    'company_type'      => $companyTypeOptions[$row['company_type']],
                    'manpower_company_id' => $manpowerCompanyID,
                    'lng'       => $languageOptions[$row['language']],
                    'gender'    => $genderOptions[$row['gender']],
                    'is_afraid_by_cat' => $row['are_you_afraid_of_cat'] == 'Yes' ? 1 : 0,
                    'is_afraid_by_dog' => $row['are_you_afraid_of_dog'] == 'Yes' ? 1 : 0,
                    'status'    => $statusOptions[$row['status']],
                ];

                $worker = User::where('phone', $workerData['phone'])
                    ->orWhere('email', $workerData['email'])
                    ->first();

                if (empty($worker)) {
                    $worker = User::create($workerData);
                }
            } catch (Exception $e) {
                Log::error($e);
                $failedImports->push($row);
                continue;
            }
        }
    }
}
