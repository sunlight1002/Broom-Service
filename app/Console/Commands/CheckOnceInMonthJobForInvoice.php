<?php

namespace App\Console\Commands;

use App\Jobs\GenerateJobInvoice;
use App\Models\Order;
use App\Traits\PaymentAPI;
use Illuminate\Console\Command;

class CheckOnceInMonthJobForInvoice extends Command
{
    use PaymentAPI;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:check-once-in-month';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoice for jobs once in month';

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
        $orders = Order::query()
            ->with(['jobs:jobservice,client,contract,order'])
            ->has('jobs', '=', 1)
            ->where('invoice_status', '0')
            ->limit(10)
            ->where('status', 'Open')
            ->get();

        foreach ($orders as $order) {
            $job = $order->job[0];

            if ($job->is_one_time_in_month_job) {
                GenerateJobInvoice::dispatch($order->id);
            }
        }

        return 0;
    }
}
