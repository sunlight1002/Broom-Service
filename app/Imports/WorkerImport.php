<?php

namespace App\Imports;

use App\Models\Countries;
use App\Models\ManpowerCompany;
use App\Models\User;
use App\Models\WorkerAvailability;
use App\Traits\PaymentAPI;
use App\Enums\Form101FieldEnum;
use App\Enums\WorkerFormTypeEnum;
use App\Events\WorkerCreated;
use App\Events\SendWorkerLogin;
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
use Carbon\Carbon;

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

                if(isset($row['country'])) {
                    $country = $countries->where('code', $row['country'])->first();
                    // if (!$country) {
                    //     throw new Exception('Invalid country');
                    // }
                }

                $manpowerCompanyID = NULL;
                if ($row['company_type'] == 'Manpower') {
                    $manpowerCompany = $manpowerCompanies->where('name', $row['manpower'])->first();
                    if (!$manpowerCompany && !empty($row['manpower'])) {
                        $manpowerCompany = ManpowerCompany::Create(['name' => $row['manpower']]);
                        // throw new Exception('Invalid manpower company');
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
                    'country'   => $country->name ?? NULL,
                    'company_type'      => $companyTypeOptions[$row['company_type']],
                    'manpower_company_id' => $manpowerCompanyID,
                    'lng'       => $languageOptions[$row['language']],
                    'gender'    => $genderOptions[$row['gender']],
                    'is_afraid_by_cat' => $row['are_you_afraid_of_cat'] == 'Yes' ? 1 : 0,
                    'is_afraid_by_dog' => $row['are_you_afraid_of_dog'] == 'Yes' ? 1 : 0,
                    'status'    => $statusOptions[$row['status']],
                    'form101'   => $row['form101'] == 'Yes' ? 1 : 0,
                    'contract'  => $row['contract'] == 'Yes' ? 1 : 0,
                    'saftey_and_gear' => $row['saftey_and_gear'] == 'Yes' ? 1 : 0,
                    'insurance'   => $row['insurance'] == 'Yes' ? 1 : 0,
                    'is_imported' => 1,
                ];

                $worker = User::where('phone', $workerData['phone'])
                    ->orWhere('email', $workerData['email'])
                    ->first();

                if (empty($worker)) {
                    $worker = User::create($workerData);

                    // Send Login credentials to Worker
                    event(new SendWorkerLogin($worker->toArray()));

                    $i = 1;
                    $j = 0;
                    $check_friday = 1;
                    while ($i == 1) {
                        $current = Carbon::now();
                        $day = $current->addDays($j);
                        if ($this->isWeekend($day->toDateString())) {
                            $check_friday++;
                        } else {
                            $w_a = new WorkerAvailability;
                            $w_a->user_id = $worker->id;
                            $w_a->date = $day->toDateString();
                            $w_a->start_time = '08:00:00';
                            $w_a->end_time = '17:00:00';
                            $w_a->status = 1;
                            $w_a->save();
                        }
                        $j++;
                        if ($check_friday == 6) {
                            $i = 2;
                        }
                    }

                } else {
                    $worker->update([
                        'firstname' => $row['first_name'] ?? $worker->firstname,
                        'lastname'  => $row['last_name'] ?? $worker->lastname,
                        'phone'     => $row['phone'] ?? $worker->phone,
                        'role'      => $row['role'] ?? $worker->role,
                        'address'           => $row['full_address'] ?? $worker->address,
                        'payment_per_hour'  => $row['payment_per_hour'] ?? $worker->payment_per_hour,
                        'renewal_visa'      => date('Y-m-d', strtotime($row['renewal_of_visa'] ?? $worker->renewal_visa)),
                        'worker_id'         => $row['worker_id'] ?? $worker->worker_id,
                        'passcode'  => $row['password'] ?? $worker->passcode,
                        'password'  => Hash::make($row['password'] ?? $worker->password),
                        'country'   => $country->name ?? $worker->country,
                        'company_type'      => $companyTypeOptions[$row['company_type']],
                        'manpower_company_id' => $manpowerCompanyID,
                        'lng'       => $languageOptions[$row['language']],
                        'gender'    => $genderOptions[$row['gender']],
                        'is_afraid_by_cat' => $row['are_you_afraid_of_cat'] == 'Yes' ? 1 : 0,
                        'is_afraid_by_dog' => $row['are_you_afraid_of_dog'] == 'Yes' ? 1 : 0,
                        'status'    => $statusOptions[$row['status']],
                        'form101'   => $row['form101'] == 'Yes' ? 1 : 0,
                        'contract'  => $row['contract'] == 'Yes' ? 1 : 0,
                        'saftey_and_gear' => $row['saftey_and_gear'] == 'Yes' ? 1 : 0,
                        'insurance'   => $row['insurance'] == 'Yes' ? 1 : 0,
                        'is_imported' => 1,
                    ]);
                }

                if($row['form101'] == 'Yes') {
                    $formEnum = new Form101FieldEnum;

                    $defaultFields = $formEnum->getDefaultFields();
                    $defaultFields['employeeFirstName'] = $worker->firstname;
                    $defaultFields['employeeLastName'] = $worker->lastname;
                    $defaultFields['employeeMobileNo'] = $worker->phone;
                    $defaultFields['employeeEmail'] = $worker->email;
                    $defaultFields['sender']['employeeEmail'] = $worker->email;
                    $defaultFields['employeeSex'] = Str::ucfirst($worker->gender);
                    $formData = app('App\Http\Controllers\User\Auth\AuthController')->transformFormDataForBoolean($defaultFields);

                    $worker->forms()->create([
                        'type' => WorkerFormTypeEnum::FORM101,
                        'data' => $formData,
                        'submitted_at' => NULL
                    ]);
                }

                event(new WorkerCreated($worker));

            } catch (Exception $e) {
                Log::error($e);
                $failedImports->push($row);
                continue;
            }
        }
    }

    public function isWeekend($date)
    {
        $weekDay = date('w', strtotime($date));
        return ($weekDay == 5 || $weekDay == 6);
    }
}
