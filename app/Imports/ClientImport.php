<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\ClientPropertyAddress;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ClientImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $failedImports = collect([]);
        foreach ($collection as $row) {
            try {
                $validator = Validator::make($row->toArray(), [
                    'first_name' => ['required', 'string', 'max:255'],
                    'primary_phone'     => ['required'],
                    'status'    => ['required'],
                    'password'  => ['required', 'string', 'min:6',],
                    'primary_email'     => ['required', 'string', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    $failedImports->push($row);
                    continue;
                }

                $extra = [];
                if (!empty($row['alternate_email_1']) || !empty($row['person_name_1']) || !empty($row['alternate_phone_1'])) {
                    $extra[] = [
                        'email' => $row['alternate_email_1'] ?? '',
                        'name' => $row['person_name_1'] ?? '',
                        'phone' => $row['alternate_phone_1'] ?? '',
                    ];
                }
                if (!empty($row['alternate_email_2']) || !empty($row['person_name_2']) || !empty($row['alternate_phone_2'])) {
                    $extra[] = [
                        'email' => $row['alternate_email_2'] ?? '',
                        'name' => $row['person_name_2'] ?? '',
                        'phone' => $row['alternate_phone_2'] ?? '',
                    ];
                }

                $status = [
                    'Lead' => 0,
                    'Potential Customer' => 1,
                    'Customer' => 2,
                ];
                if (!in_array(Str::title($row['status'] ?? ''), array_keys($status))) {
                    throw new Exception('Invalid client status');
                }
                $clientData = [
                    'firstname' => $row['first_name'] ?? '',
                    'lastname' => $row['last_name'] ?? '',
                    'invoicename' => $row['invoice_name'] ?? '',
                    'dob' => date('Y-m-d', strtotime($row['date_of_birth'] ?? '')),
                    'phone'     => $row['primary_phone'] ?? '',
                    'status'    => $status[Str::title($row['status'] ?? '')],
                    'passcode'  => $row['password'] ?? '',
                    'password'  => Hash::make($row['password'] ?? ''),
                    'email'     => $row['primary_email'] ?? '',
                    'extra' => json_encode($extra),
                    'lng'     => $row['language'] ?? '',
                    'color'     => $row['color'] ?? '',
                    'payment_method'     => $row['payment_method'] ?? '',
                ];

                $client = Client::where('phone', $clientData['phone'])
                    ->orWhere('email', $clientData['email'])
                    ->first();

                if (empty($client)) {
                    $client = Client::create($clientData);
                }

                // Create client address if not already exists
                if (!ClientPropertyAddress::where('address_name', $row['address_name'] ?? '')->where('client_id', $client->id)->exists()) {
                    ClientPropertyAddress::create([
                        'address_name' => $row['address_name'] ?? '',
                        'floor' => $row['floor'] ?? '',
                        'apt_no' => $row['apt_number_and_apt_name'] ?? '',
                        'entrence_code' => $row['enterance_code'] ?? '',
                        'zipcode' => $row['zip_code'] ?? '',
                        'geo_address' => $row['full_address'] ?? '',
                        'latitude' => $row['lat'] ?? '',
                        'longitude' => $row['lng'] ?? '',
                        'city' => $row['city'] ?? '',
                        'client_id' => $client->id,
                        'prefer_type' => $row['prefered_type'] ?? '',
                        'is_dog_avail' => strtolower($row['dog_in_the_property'] ?? '') == 'yes' ? 1 : 0,
                        'is_cat_avail' => strtolower($row['cat_in_the_property'] ?? '') == 'yes' ? 1 : 0,
                    ]);
                }
            } catch (Exception $e) {
                $failedImports->push($row);
                continue;
            }
        }
    }
}
