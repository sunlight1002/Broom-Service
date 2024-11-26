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
    protected $clientID;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($orderID, $clientID)
    {
        $this->orderID = $orderID;
        $this->clientID = $clientID;

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
        // Fetch orders for the client with the specified conditions
        $orders = Order::query()
            ->with('client') // Eager load client relationship
            ->where(function ($q) {
                $q
                    ->where('invoice_status', '0')
                    ->orWhere('invoice_status', '1');
            })
            ->where('client_id', $this->clientID)
            ->where('status', 'Open')
            ->get();
    
        // \Log::info(['Orders: ' => $orders]);
    
        // Ensure orders exist
        if ($orders->isNotEmpty()) {
            // Fetch the client once, assuming all orders belong to the same client
            $client = $orders->first()->client;
    
            if (!$client) {
                \Log::error("Client not found for client_id: {$this->clientID}");
                return;
            }
    
            // Fetch the client's card details
            $card = $this->getClientCard($this->clientID);
    
            $payment_method = $client->payment_method;
    
            if (($payment_method == 'cc' && $card) || $payment_method != 'invoice') {
                $this->generateOrderInvoice($client, $orders, $card);
            } else {
                \Log::warning("No valid payment method or card found for client_id: {$this->clientID}");
            }
        } else {
            \Log::info("No orders found for client_id: {$this->clientID}");
        }
    }
    
}
