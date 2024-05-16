<?php

namespace App\Jobs;

use App\Enums\OrderPaidStatusEnum;
use App\Models\Order;
use App\Traits\ClientCardTrait;
use App\Traits\ICountDocument;
use App\Traits\PaymentAPI;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateJobInvoice implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels,
        PaymentAPI,
        ClientCardTrait,
        ICountDocument;

    protected $orderID;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($orderID)
    {
        $this->orderID = $orderID;
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = Order::query()
            ->where(function ($q) {
                $q
                    ->where('invoice_status', '0')
                    ->orWhere('invoice_status', '1');
            })
            ->where('status', 'Open')
            ->find($this->orderID);

        if ($order) {
            $client = $order->client;

            $card = $this->getClientCard($client->id);

            $payment_method = $client->payment_method;

            if (
                ($payment_method == 'cc' && $card) ||
                $payment_method != 'invoice'
            ) {
                $this->generateOrderInvoice($client, $order, $card);
            }
        }
    }
}
