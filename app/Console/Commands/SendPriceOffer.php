<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Offer;
use Illuminate\Support\Facades\Http;

class SendPriceOffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'priceoffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send client price offer message';

    protected $whapiApiEndpoint, $whapiApiToken;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clients = Client::all();

        foreach ($clients as $client) {
            $offer = Offer::where('client_id', $client->id)->first();

            if ($offer) {
                $message = " שלום {$client->firstname},

                            אנו שמחים להודיע על המעבר למערכת חדשה ויעילה שתשפר את תהליך העבודה שלנו מולכם. 
                            בקרוב ישלח אליכם הסכם חדש לחתימה דרך המערכת החדשה.

                            שימו לב, בהסכם החדש תתבקשו להזין פרטי כרטיס אשראי בצורה מאובטחת, אשר יחוייב אחת לחודש, לאחר קבלת השירות האחרון שלכם מאיתנו באותו חודש.

                            נשמח לעמוד לרשותכם בכל שאלה או בקשה.

                            בברכה,
                            צוות ברום סרוויס";
            } else {
                $message = " שלום {$client->firstname},

                            אנו שמחים להודיע על המעבר למערכת חדשה ויעילה שתשפר את תהליך העבודה שלנו מולכם. 
                            בקרוב תישלח אליכם הצעת מחיר חדשה לאישורכם. לאחר אישור ההצעה, ישלח אליכם הסכם לחתימה.

                            בהסכם החדש תתבקשו להזין פרטי כרטיס אשראי בצורה מאובטחת, אשר יחוייב אחת לחודש, לאחר קבלת השירות האחרון שלכם מאיתנו באותו חודש.

                            נשמח לעמוד לרשותכם בכל שאלה או בקשה.

                            בברכה,
                            צוות ברום סרוויס";
            }

            $this->sendWhatsAppMessage($client->phone, $message);
        }

        return 0;
    }

    /**
     * Send WhatsApp message via the WHAPI API
     *
     * @param string $phoneNumber
     * @param string $message
     * @return void
     */
    protected function sendWhatsAppMessage($phoneNumber, $message)
    {
  
        $response = Http::withToken($this->whapiApiToken)
                        ->post($this->whapiApiEndpoint . 'messages/text', [
                            'to' => $phoneNumber . '@s.whatsapp.net',
                            'body' => $message
                        ]);

        if ($response->successful()) {
            $this->info("Message sent to {$phoneNumber}");
        } else {
            $this->error("Failed to send message to {$phoneNumber}");
        }
    }
}
