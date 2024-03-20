<?php

namespace App\Traits;

use App\Models\ClientPropertyAddress;

trait PriceOffered
{
    private function formatServices($offer, $returnJson = true)
    {
        $services = json_decode($offer['services']);

        if (isset($services)) {
            foreach ($services as $service) {
                if (!empty($service->address)) {
                    $service->address = ClientPropertyAddress::find($service->address)->toArray();
                }
            }
        }

        if ($returnJson) {
            return json_encode($services, true);
        } else {
            return $services;
        }
    }
}
