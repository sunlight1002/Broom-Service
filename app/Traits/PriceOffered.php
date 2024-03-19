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

    private function calcAmount($services)
    {
        $tax_percentage = config('services.app.tax_percentage');
        $subtotal = 0;
        foreach ($services as $key => $service) {
            if ($service['type'] == 'hourly') {
                foreach ($service['workers'] as $key => $worker) {
                    $subtotal += $service['rateperhour'] * $worker['jobHours'];
                }
            } else {
                $subtotal += $service['fixed_price'] * count($service['workers']);
            }
        }

        $tax_amount = ($tax_percentage / 100) * $subtotal;

        return [$subtotal, $tax_amount];
    }
}
