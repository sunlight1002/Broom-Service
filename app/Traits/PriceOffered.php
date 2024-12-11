<?php

namespace App\Traits;

use App\Models\ClientPropertyAddress;
use App\Models\JobService;
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
                    $jobService = JobService::find($service['service']);
                    $services[$key]['service_name_heb'] = $jobService->heb_name ?? 'לא ידוע';
                }
    
                // Fetch ServiceSchedule details
                if (!empty($service['frequency'])) {
                    $serviceSchedule = ServiceSchedule::find($service['frequency']);
                    $services[$key]['frequency_name_heb'] = $serviceSchedule->name_heb ?? 'ddcd';
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
