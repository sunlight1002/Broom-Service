<?php

namespace App\Traits;

use App\Models\ClientPropertyAddress;

trait PriceOffered
{
    private function formatServices($offer, $returnJson = true)
    {
        $services = json_decode($offer['services'], true);

        if (isset($services)) {
            foreach ($services as $key => $service) {
                if (!empty($service['address'])) {
                    $services[$key]['address'] = ClientPropertyAddress::find($service['address'])->toArray();
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
