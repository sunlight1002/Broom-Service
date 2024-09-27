<?php

namespace Database\Seeders;

use App\Models\Offer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class FixOldOffersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $currectOffer = Offer::find(1114);

        // dd(json_decode($currectOffer->services, true));
        $offers = Offer::where('is_fixed_for_services', false)->get();


        print_r('count:' . $offers->count());
        echo "\n";
        $tax_percentage = config('services.app.tax_percentage');
        foreach ($offers as $key => $offer) {
            $oldServices = json_decode($offer->services, true);

            $newServices = $oldServices;

            $subtotal = 0;

            foreach ($newServices as $skey => $_service) {
                if (isset($newServices[$skey]['jobHours'])) {
                    unset($newServices[$skey]['jobHours']);
                }

                $newServices[$skey]['weekdays'] = [];
                $newServices[$skey]['weekday_occurrence'] = "1";
                $newServices[$skey]['weekday'] = "sunday";
                $newServices[$skey]['month_occurrence'] = 1;
                $newServices[$skey]['month_date'] = 1;
                $newServices[$skey]['monthday_selection_type'] = 'weekday';

                if (!isset($newServices[$skey]['workers'])) {
                    $jobHours = '';

                    if ($oldServices[$skey]['jobHours'] == '4 / 2') {
                        $jobHours = 2;
                    } else {
                        $jobHours = $oldServices[$skey]['jobHours'];

                        if (!ctype_digit($jobHours) && $jobHours != '') {
                            $jobHours = (string)(int)ceil($jobHours);
                        }
                    }

                    $newServices[$skey]['workers'] = [
                        [
                            'jobHours' => $jobHours
                        ]
                    ];
                }

                $serviceTotal = 0;
                if ($newServices[$skey]['type'] == 'hourly') {
                    foreach ($newServices[$skey]['workers'] as $wkey => $worker) {
                        $serviceTotal += $newServices[$skey]['rateperhour'] * $worker['jobHours'];
                    }
                    $subtotal += $serviceTotal;
                } else if($newServices[$skey]['type'] == 'squaremeter') {
                    $serviceTotal += $newServices[$skey]['ratepersquaremeter'] * $newServices[$skey]['totalsquaremeter'];
                } else {
                    if ($newServices[$skey]['fixed_price'] == '') {
                        $newServices[$skey]['fixed_price'] = 0;
                    }

                    $serviceTotal += $newServices[$skey]['fixed_price'];
                    $subtotal += $serviceTotal;
                }
                $newServices[$skey]['totalamount'] = $serviceTotal;
            }

            $tax_amount = ($tax_percentage / 100) * $subtotal;

            $offer->update([
                'is_fixed_for_services' => true,
                'services' => json_encode($newServices, JSON_UNESCAPED_UNICODE),
                'subtotal' => $subtotal,
                'total' => $subtotal + $tax_amount
            ]);

            print_r($offer->id);
            echo "\n";
        }
    }
}
