<?php

namespace App\Console\Commands;

use App\Jobs\GenerateJobInvoice;
use App\Models\Invoices;
use App\Models\Job;
use App\Models\Order;
use App\Traits\PaymentAPI;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRegularInvoice extends Command
{
    use PaymentAPI;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regular-invoice:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate regular invoice';

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
        $today = Carbon::today();
        if (!$today->isLastOfMonth()) {
            return 0;
        }

        $orders = Order::query()
            ->where('invoice_status', '1')
            ->where('status', 'Open')
            ->get();

        foreach ($orders as $key => $order) {
            GenerateJobInvoice::dispatch($order->id)
                ->delay(now()->addMinutes(1 + (($key) * 2)));
        }

        return 0;
    }
}
