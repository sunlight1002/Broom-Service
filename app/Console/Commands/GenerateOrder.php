<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate order';

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
        $jobs = Job::query()
            ->with(['jobservice', 'client'])
            ->where([
                'start_date' => Carbon::today()->format('Y-m-d'),
                'isOrdered' => 0
            ])
            ->get();

        $_shifts = [
            'fullday-8am-16pm'   => '08:30:00-16:00:00',
            'morning'            => '08:30:00-10:00:10',
            'morning1-8am-10am'  => '08:30:00-10:00:00',
            'morning2-10am-12pm' => '10:30:00-12:00:00',
            'morning-8am-12pm'   => '08:30:00-12:00:00',
            'noon'               => '12:30:00-14:00:00',
            'noon1-12pm-14pm'    => '12:30:00-14:00:00',
            'noon2-14pm-16pm'    => '14:30:00-16:00:00',
            'noon-12pm-16pm'     => '12:30:00-16:00:00',
            'evening'            => '16:30:00-18:00:00',
            'evening1-16pm-18pm' => '16:30:00-18:00:00',
            'evening2-18pm-20pm' => '18:30:00-20:00:00',
            'evening-16pm-20pm'  => '16:30:00-20:00:00',
            'night'              => '20:30:00-22:00:00',
            'night1-20pm-22pm'   => '20:30:00-22:00:00',
            'night2-22pm-24pm'   => '22:30:00-00:00:00',
            'night-20pm-24pm'    => '20:30:00-00:00:00',
        ];

        foreach ($jobs as $job) {
            $t     = $_shifts[str_replace(' ', '', $job->shifts)];
            $et    = explode('-', $t);

            $_start = Carbon::today()->format('Y-m-d ' . $et[0] . ':00');
            $_end   = Carbon::today()->format('Y-m-d ' . $et[1] . ':00');
            $_now   = Carbon::now()->format('Y-m-d H:i:s');

            $start  = Carbon::createFromFormat('Y-m-d H:i:s', $_start);
            $end    = Carbon::createFromFormat('Y-m-d H:i:s',  $_end);
            $now    = Carbon::createFromFormat('Y-m-d H:i:s',  $_now);

            if (($start->lt($now)) && ($end->gt($now))) {
                $service = $job->jobservice;
                $items = [
                    [
                        "description" => $service->name . " - " . Carbon::today()->format('d, M Y'),
                        "unitprice"   => $service->total,
                        "quantity"    => 1,
                    ]
                ];

                $this->generateOrderDocument($job->client, [$job->id], $items, $job->is_one_time_job);
            }
        }
    }
}
