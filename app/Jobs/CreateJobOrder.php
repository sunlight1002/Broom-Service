<?php

namespace App\Jobs;

use App\Models\Job;
use App\Traits\PaymentAPI;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateJobOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, PaymentAPI;

    protected $jobID;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($jobID)
    {
        $this->jobID = $jobID;
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $job = Job::query()
            ->with(['client', 'jobservice'])
            ->where('is_order_generated', false)
            ->where('is_paid', false)
            ->where('is_job_done', true)
            ->find($this->jobID);

        if ($job) {
            $client = $job->client;
            $service = $job->jobservice;

            $items = [
                [
                    'description' => $client->lng == 'heb' ? $service->heb_name : $service->name,
                    'unitprice' => $service->total,
                    'quantity' => 1
                ]
            ];

            $dueDate = Carbon::today()->endOfMonth()->toDateString();

            $order = $this->generateOrderDocument(
                $client,
                [$job->id],
                $items,
                $dueDate,
                $job->is_one_time_in_month_job
            );

            if ($job->is_one_time_in_month_job) {
                GenerateJobInvoice::dispatch($order->id);
            }
        }
    }
}
