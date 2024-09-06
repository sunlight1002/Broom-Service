<?php

namespace App\Traits;

use App\Models\ClientPropertyAddress;

trait PriceOffered
{
    private function formatServices($offer, $returnJson = true)
    {
        $services = isset($offer['services']) ? json_decode($offer['services'], true) : [];

        if (isset($services)) {
            foreach ($services as $key => $service) {
               if (!empty($service['address'])) {
                    $address = ClientPropertyAddress::find($service['address']);
                    if ($address) {
                        $services[$key]['address'] = $address->toArray();
                    } else {
                        // Handle the case where the address is not found
                        $services[$key]['address'] = null; // or you can set a default value
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
