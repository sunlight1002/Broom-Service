<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\Client;
use App\Models\Offer;
use App\Models\ServiceSchedule;
use App\Models\Services;
use App\Models\ClientPropertyAddress;

use Exception;
use Illuminate\Support\Str;

class ClientOfferImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
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
                    'client_email' => ['required', 'string', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    $failedImports->push($row);
                    continue;
                }

                $client = Client::Where('email', $row['client_email'])->first();
                $clientpropertyaddress = ClientPropertyAddress::Where('client_id', $client->id)->first();

                if (empty($client) || empty($clientpropertyaddress)) {
                    $failedImports->push($row);
                    continue;
                }

                $clientpropertyaddress = ClientPropertyAddress::Where('client_id', $client->id)
                    // ->Where('address_name', $row['address'])
                    ->first();

                $offer = Offer::where('client_id', $client->id)->where('status', 'sent')->first();

                $existing_services = [];
                if (!empty($offer)) {
                    $existing_services = json_decode($offer->services);
                }

                $service = Services::Where('name', $row['service_name'])->first();
                $serviceschedule = ServiceSchedule::Where('name', $row['frequency'])->first();

                $total_amount = $row['fixed_price'];

                if ($row['type'] == 'hourly') {
                    $total_amount = $row['job_hours'] * $row['fixed_price'];
                }

                $services = [
                    'service' => $service->id ?? '',
                    'name' => $row['service_name'] ?? '',
                    'type' => $row['type'] ?? '',
                    'jobHours' => $row['job_hours'] ?? '',
                    'rateperhour' => ($row['type'] == 'hourly') ? $row['fixed_price'] : '',
                    'freq_name' => $row['frequency'] ?? '',
                    'frequency' => $serviceschedule->id ?? '',
                    'fixed_price' => ($row['type'] == 'fixed') ? $row['fixed_price'] : '',
                    'other_title' => ($row['frequency'] == 'Others') ? $row['other_title'] : '',
                    'totalamount' => $total_amount,
                    'template' => $service->template ?? '',
                    'cycle' => $serviceschedule->cycle ?? '',
                    'period' => $serviceschedule->period ?? '',
                    'address' => $clientpropertyaddress->id ?? '',
                    'start_date' => $row['start_date'] ?? '',
                    "weekdays" => [],
                    "weekday_occurrence" => "1",
                    "weekday" => "sunday",
                    "month_occurrence" => 1,
                    "month_date" => 1,
                    "monthday_selection_type" => "weekday"
                ];

                $existing_services[] = $services;

                $to = 0;
                $taxper = config('services.app.tax_percentage');

                if (is_array($existing_services)) {
                    foreach ($existing_services as $existing_service) {
                        if (is_array($existing_service)) {
                            if (isset($existing_service['type']) && $existing_service['type'] == 'hourly') {
                                if (
                                    isset($existing_service['jobHours']) && is_numeric($existing_service['jobHours']) &&
                                    isset($existing_service['rateperhour']) && is_numeric($existing_service['rateperhour'])
                                ) {
                                    $to = $to + ($existing_service['jobHours'] * $existing_service['rateperhour']);
                                }
                            } else {
                                if (isset($existing_service['fixed_price']) && is_numeric($existing_service['fixed_price'])) {
                                    $to = $to + $existing_service['fixed_price'];
                                }
                            }
                        } else {
                            if ($existing_service->type == "hourly") {
                                $to = $to + ($existing_service->jobHours * $existing_service->rateperhour);
                            } else {
                                $to = $to + $existing_service->fixed_price;
                            }
                        }
                    }
                }

                $tax = ($taxper / 100) * $to;

                if (!$offer) {
                    Offer::create([
                        'client_id' => $client->id,
                        'services' => json_encode($existing_services),
                        'subtotal' => $to,
                        'total' => ($to + $tax),
                        'status' => 'sent',
                    ]);
                } else {
                    Offer::where('id', $offer->id)->update([
                        'services' => json_encode($existing_services),
                        'subtotal' => $to,
                        'total' => ($to + $tax),
                        'status' => 'sent',
                    ]);
                }
            } catch (Exception $e) {
                $failedImports->push($row);
                continue;
            }
        }
    }
}
