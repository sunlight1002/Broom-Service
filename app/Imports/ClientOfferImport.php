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
                    ->first();

                $offer = Offer::where('client_id', $client->id)->where('status', 'sent')->first();

                $existing_services = [];
                if ($offer) {
                    $existing_services = json_decode($offer->services);
                }

                $service = Services::Where('name', $row['service_name'])->first();
                $serviceschedule = ServiceSchedule::Where('name', $row['frequency'])->first();

                $total_amount = 0;

                $workerJobHours = [];
                if (!empty($row['worker_hours'])) {
                    $workerhours  = explode(',', $row['worker_hours']);
                    foreach ($workerhours as $workerhour) {
                        array_push($workerJobHours,  array('jobHours' => $workerhour));
                    }
                }

                if ($row['type'] == 'hourly') {
                    foreach ($workerJobHours as $key => $worker) {
                        $total_amount += $worker['jobHours'] * $row['fixed_price'];
                    }
                } else {
                    $total_amount += $row['fixed_price'];
                }

                $services = [
                    'service' => $service->id ?? '',
                    'name' => $row['service_name'] ?? '',
                    'type' => $row['type'] ?? '',
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
                    'workers' => $workerJobHours,
                    "weekdays" => [],
                    "weekday_occurrence" => "1",
                    "weekday" => "sunday",
                    "month_occurrence" => 1,
                    "month_date" => 1,
                    "monthday_selection_type" => "weekday"
                ];

                $existing_services[] = $services;

                $subtotal = 0;
                $tax_percentage = config('services.app.tax_percentage');

                foreach ($existing_services as $existing_service) {
                    if (isset($existing_service['type']) && $existing_service['type'] == 'hourly') {
                        foreach ($workerJobHours as $key => $worker) {
                            if (
                                isset($worker['jobHours']) && is_numeric($worker['jobHours']) &&
                                isset($existing_service['rateperhour']) && is_numeric($existing_service['rateperhour'])
                            ) {
                                $subtotal += ($worker['jobHours'] * $existing_service['rateperhour']);
                            }
                        }
                    } else {
                        if (isset($existing_service['fixed_price']) && is_numeric($existing_service['fixed_price'])) {
                            $subtotal += ($existing_service['fixed_price'] * count($workerJobHours));
                        }
                    }
                }

                $tax = ($tax_percentage / 100) * $subtotal;

                if (!$offer) {
                    Offer::create([
                        'client_id' => $client->id,
                        'services' => json_encode($existing_services),
                        'subtotal' => $subtotal,
                        'total' => ($subtotal + $tax),
                        'status' => 'sent',
                    ]);
                } else {
                    Offer::where('id', $offer->id)->update([
                        'services' => json_encode($existing_services),
                        'subtotal' => $subtotal,
                        'total' => ($subtotal + $tax),
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
