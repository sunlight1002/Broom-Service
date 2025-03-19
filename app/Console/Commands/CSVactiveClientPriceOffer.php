<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Offer;
use App\Models\Setting;
use App\Enums\SettingKeyEnum;
use App\Models\ClientPropertyAddress;
use Illuminate\Support\Facades\Http;
use SplTempFileObject;
use League\Csv\Writer;
use Illuminate\Support\Facades\Storage;

class CSVactiveClientPriceOffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:activeClientPriceOffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Csv active client price offer';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $headers = [
            'Client ID', 'Offer ID', 'Full Name', 'Invoice Name', 'Phone', 'Service name', 'Frequency name', 'Hours', 'Price', '< 100',
        ];

        $offers = Offer::with(['client.lead_status'])
                ->where('status', 'accepted')
                ->whereHas('client', function ($query) {
                    $query->where('status', 2)
                        ->whereHas('lead_status', function ($leadQuery) {
                            $leadQuery->where('lead_status', 'active client'); // Add conditions if needed
                        });
                })
                ->get();

        // Create CSV writer instance
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        // Insert the headers
        $csv->insertOne($headers);

        foreach ($offers as $offer) {
            $client = $offer->client;
            $offerService = json_decode($offer->services, true);

            foreach ($offerService as $service) {
                if ($service['type'] == 'fixed') {
                    $price = $service['fixed_price'] / $service['workers'][0]['jobHours'];
                    if($price < 100){
                        $csv->insertOne([
                            $client->id,
                            $offer->id,
                            ($client->firstname ?? null) . ' ' . ($client->lastname ?? null),
                            $client->invoicename ?? null,
                            $client->phone ?? null,
                            $service['name'] ?? null,
                            $service['freq_name'] ?? null,
                            $service['workers'][0]['jobHours'] ?? null,
                            $service['fixed_price'] ?? null,
                            round($price, 2)
                        ]);
                    }
                }
            }
        }

        $fileName = 'active-client-price-offer.csv';
        Storage::put($fileName, $csv->toString());

        $this->info("Report has been exported to storage/{$fileName}");

        return 0;
    }
}
