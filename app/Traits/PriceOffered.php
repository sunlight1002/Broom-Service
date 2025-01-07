<?php

namespace App\Traits;

use App\Models\ClientPropertyAddress;
use App\Models\Services;
use App\Models\subservices;
use App\Models\ServiceSchedule;

trait PriceOffered
{
    private function formatServices($offer, $returnJson = true)
    {
        $services = isset($offer['services']) ? json_decode($offer['services'], true) : [];

        if (!empty($services)) {
            foreach ($services as $key => $service) {
                // Fetch and format address
                if (!empty($service['address'])) {
                    $address = ClientPropertyAddress::find($service['address']);
                    $services[$key]['address'] = $address ? $address->toArray() : null;
                }

                // Fetch JobService details
                if (!empty($service['service'])) {
                    $jobService = Services::find($service['service']);
                    $services[$key]['service_name_heb'] = $jobService->heb_name ?? 'לא ידוע';
                    $services[$key]['service_name_en'] = $jobService->name ?? "not found";
                }

                // Fetch ServiceSchedule details
                if (!empty($service['frequency'])) {
                    $serviceSchedule = ServiceSchedule::find($service['frequency']);
                    $services[$key]['frequency_name_heb'] = $serviceSchedule->name_heb ?? 'לא ידוע';
                    $services[$key]['frequency_name_en'] = $serviceSchedule->name ?? "not found";
                }

                if(!empty($service['sub_services']["id"])) {
                    $subServices = subservices::find($service['sub_services']["id"]);
                    $services[$key]['sub_services']['subServices'] = $subServices ? $subServices->toArray() : null;

                }

                // Add sub_services address and fulladdress for template "airbnb"
                if (!empty($service['template']) && $service['template'] === 'airbnb') {
                    if (isset($service['sub_services']) && !empty($service['sub_services']['address'])) {
                        $subServiceAddress = ClientPropertyAddress::find($service['sub_services']['address']);
                        $services[$key]['sub_services']['fulladdress'] = $subServiceAddress ? $subServiceAddress->toArray() : null;
                    }
                }
            }
        }

        if ($returnJson) {
            return json_encode($services, JSON_UNESCAPED_UNICODE);
        } else {
            return $services;
        }
    }
}
