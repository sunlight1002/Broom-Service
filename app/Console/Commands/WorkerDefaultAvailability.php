<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WorkerDefaultAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:default-availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update default availability of workers for next 2 weeks';

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
        $todayDay = Carbon::today();
        $lastDate = Carbon::today()->endOfWeek(Carbon::SATURDAY)->addMonths(6);

        $lastFriday = $lastDate->clone()->subDay();

        $workers = User::with('availabilities')
            ->whereDoesntHave('availabilities', function ($q) use ($lastFriday) {
                $q->where('date', $lastFriday);
            })
            ->get(['id']);

        foreach ($workers as $key => $worker) {

            if ($worker->availabilities->count()) {
                $lastAvailDate = $worker->availabilities->sortBy('date')->last()->toArray()['date'];

                $currentDay = Carbon::parse($lastAvailDate);
                $diffInDays = $currentDay->diffInDays($lastFriday, false);
                for ($i = 0; $i <= $diffInDays; $i++) {
                    if ($currentDay->dayOfWeek <= 5) {
                        $dateInString = $currentDay->toDateString();

                        if (!$worker->availabilities->where('date', $dateInString)->count()) {
                            $worker->availabilities()->updateOrCreate(['date' => $dateInString], [
                                'date' => $dateInString,
                                'start_time' => '08:00:00',
                                'end_time' => '17:00:00',
                                'status' => '1',
                            ]);
                        }
                    }

                    $currentDay->addDay();
                }
            } else {
                $diffInDays = $todayDay->diffInDays($lastFriday, false);

                $currentDay = today();
                for ($i = 0; $i <= $diffInDays; $i++) {
                    if ($currentDay->dayOfWeek <= 5) {
                        $dateInString = $currentDay->toDateString();

                        $worker->availabilities()->updateOrCreate(['date' => $dateInString],[
                            'date' => $dateInString,
                            'start_time' => '08:00:00',
                            'end_time' => '17:00:00',
                            'status' => '1',
                        ]);
                    }

                    $currentDay->addDay();
                }
            }
        }

        return 0;
    }
}
