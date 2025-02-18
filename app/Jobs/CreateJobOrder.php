<?php

namespace App\Jobs;

use App\Enums\CancellationActionEnum;
use App\Enums\JobStatusEnum;
use App\Events\ClientOrderWithDiscount;
use App\Events\ClientOrderWithExtra;
use App\Models\Job;
use App\Models\JobCancellationFee;
use App\Traits\PaymentAPI;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

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
            ->where(function ($q) {
                $q
                    ->where('status', JobStatusEnum::CANCEL)
                    ->orWhere('is_job_done', true);
            })
            ->find($this->jobID);

        // \Log::info('job', $job?->toArray()??[]);

        if ($job) {
            $items = [];
            $client = $job->client;
            $service = $job->jobservice;
            // \Log::info(['service'=> $service]);

            // mark job(s) as last of month
            $monthEndDate = Carbon::parse($job->start_date)->endOfMonth()->toDateString();

            $upcomingJobCountInCurrentMonth = Job::query()
                ->where('client_id', $client->id)
                ->whereDate('start_date', '>=', $job->start_date)
                ->where(function ($q) use ($monthEndDate) {
                    $q->whereDate('start_date', '<=', $monthEndDate)
                        ->orWhereDate('next_start_date', '<=', $monthEndDate);
                })
                ->whereIn('status', [
                    JobStatusEnum::PROGRESS,
                    JobStatusEnum::SCHEDULED,
                    JobStatusEnum::UNSCHEDULED
                ])
                ->where('is_paid', false)
                ->count();

            Job::query()
                ->where('client_id', $client->id)
                ->whereDate('start_date', '<=', $monthEndDate)
                ->where(function ($q) {
                    $q
                        ->whereIn('status', [
                            JobStatusEnum::COMPLETED,
                            JobStatusEnum::CANCEL,
                        ])
                        ->orWhere('is_job_done', true);
                })
                ->where('is_paid', false)
                ->update([
                    'is_one_time_in_month_job' => $upcomingJobCountInCurrentMonth <= 0
                ]);

            $job->refresh();

            \Log::info('upcomingJobCountInCurrentMonth : ' . $upcomingJobCountInCurrentMonth . '. Job Final Refresh : ', $job?->toArray()??[]);

            App::setLocale($client->lng);

            if ($job->is_job_done) {
                $items[] = [
                    'description' => $client->lng == 'heb' ? $service->heb_name . " - " . Carbon::parse($service->created_at)->format('d.m') : $service->name . " - " . Carbon::parse($service->created_at)->format('d.m'),
                    'unitprice' => $job->subtotal_amount,
                    'quantity' => 1
                ];
                \Log::info([$items]);
            }

            // $cancellationFees = JobCancellationFee::query()
            //     ->where('job_group_id', $job->job_group_id)
            //     ->where('is_order_generated', false)
            //     ->get(['cancellation_fee_amount', 'action']);

            // foreach ($cancellationFees as $key => $fee) {
            //     $items[] = [
            //         "description" => (($client->lng == 'en') ?  $service->name : $service->heb_name) . " - " . Carbon::today()->format('d.m') . " - " . __('mail.job_status.cancellation_fee'),
            //         "unitprice"   => $fee->cancellation_fee_amount,
            //         "quantity"    => 1,
            //     ];
            // }

            $cancellationFees = JobCancellationFee::query()
                ->where('job_id', $job->id)
                ->where('is_order_generated', false)
                ->first(['cancellation_fee_amount', 'action']);

            if($cancellationFees) {
                $items[] = [
                        "description" => (($client->lng == 'en') ?  $service->name : $service->heb_name) . " - " . Carbon::today()->format('d.m') . " - " . __('mail.job_status.cancellation_fee'),
                        "unitprice"   => $cancellationFees->cancellation_fee_amount,
                        "quantity"    => 1,
                    ];
            }

            if ($job->extra_amount) {
                $items[] = [
                    "description" => (($client->lng == 'en') ?  $service->name : $service->heb_name) . " - " . Carbon::today()->format('d.m') . " - " . __('mail.job_common.extra_amount'),
                    "unitprice"   => $job->extra_amount,
                    "quantity"    => 1,
                ];
            }

            $dueDate = Carbon::today()->endOfMonth()->toDateString();

            $serviceDate = Carbon::parse($service->created_at)->format('d.m');

            $order = $this->generateOrderDocument(
                $client,
                $items,
                $dueDate,
                [
                    'job_ids' => [$job->id],
                    'is_one_time_in_month' => $job->is_one_time_in_month_job,
                    'discount_amount' => $job->discount_amount
                ],
                $serviceDate,
                $this->jobID
                
            );

            if ($job->extra_amount) {
                event(new ClientOrderWithExtra($client, $order, $job->extra_amount));
            }

            if ($job->discount_amount) {
                event(new ClientOrderWithDiscount($client, $order));
            }

            if ($service->freq_name == 'One Time' && isset($order)) {
                \Log::info("GenerateJobInvoice one time job");  
                GenerateJobInvoice::dispatch($order->id, $client->id);
            }else if ($job->is_one_time_in_month_job && isset($order)) {
                GenerateJobInvoice::dispatch(null, $client->id);

            }
        }
    }
}
