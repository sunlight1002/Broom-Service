<?php

namespace App\Exports;

use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;

class WorkerHoursExport implements FromArray
{
    protected $worker_ids = [];
    protected $start_date = NULL;
    protected $end_date = NULL;
    protected $manpowerCompanyID = '';
    protected $data = [];

    public function __construct($worker_ids, $start_date, $end_date, $manpowerCompanyID)
    {
        $this->worker_ids = $worker_ids;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->manpowerCompanyID = $manpowerCompanyID;
    }

    public function array(): array
    {
        $data = $data = Job::whereNotNull('jobs.worker_id')
            ->whereNotNull('jobs.actual_time_taken_minutes')
            ->join('users', 'jobs.worker_id', '=', 'users.id')
            ->when($this->start_date, function ($q) {
                return $q->whereDate('jobs.start_date', '>=', $this->start_date);
            })
            ->when($this->end_date, function ($q) {
                return $q->whereDate('jobs.start_date', '<=', $this->end_date);
            })
            ->when($this->manpowerCompanyID, function ($q) {
                $q->where('users.manpower_company_id', $this->manpowerCompanyID);
            })
            ->whereIn('users.id', $this->worker_ids)
            ->where(function ($q) {
                $q->whereNull('users.last_work_date')
                    ->orWhereDate('users.last_work_date', '>=', today()->toDateString());
            })
            ->select('jobs.start_date', DB::raw('CONCAT(users.firstname, " ", COALESCE(users.lastname, "")) as worker_name'), 'users.worker_id', 'users.id')
            ->selectRaw('SUM(jobs.actual_time_taken_minutes) AS time')
            ->groupBy('jobs.worker_id')
            ->groupBy('jobs.start_date')
            ->orderBy('jobs.start_date', 'desc')
            ->get();

        $worker_hours = [];
        $worker_ids = [];

        foreach ($data as $item) {
            $date = $item["start_date"];
            $worker_id = $item["worker_id"];
            $time_in_minutes = $item["time"];
            $hours = $this->round_up($time_in_minutes / 60, 2);
            if (!isset($worker_hours[$worker_id])) {
                $worker_hours[$worker_id] = [];
                $worker_ids[] = $worker_id;
            }
            $worker_hours[$worker_id][$date] = $hours;
        }

        $csv_rows = [];

        $header_row = ["Date"];
        sort($worker_ids);
        foreach ($data->unique('worker_id')->sortBy('worker_id') as $worker) {
            $header_row[] = $worker['worker_name'];
        }
        $csv_rows[] = $header_row;

        $worker_id_row = ["Worker ID"];
        sort($worker_ids);
        foreach ($worker_ids as $worker_id) {
            $worker_id_row[] = $worker_id;
        }
        $csv_rows[] = $worker_id_row;

        $dates = array_unique(array_column($data->toArray(), "start_date"));
        sort($dates);

        foreach ($dates as $date) {
            $row = [$date];
            sort($worker_ids);
            foreach ($worker_ids as $worker_id) {
                $hours_for_date = isset($worker_hours[$worker_id][$date]) ? $worker_hours[$worker_id][$date] : 0;
                $row[] = $hours_for_date;
            }
            $csv_rows[] = $row;
        }

        $row = [];
        $row[] = 'Total';
        foreach ($worker_ids as $worker_id) {
            if (isset($worker_hours[$worker_id])) {
                $sum = array_sum(array_values($worker_hours[$worker_id]));
            } else {
                $sum = 0;
            }
            $row[] = $sum;
        }

        $csv_rows[] = $row;

        return $csv_rows;
    }

    public function round_up($value, $precision)
    {
        $pow = pow(10, $precision);
        return (ceil($pow * $value) + ceil($pow * $value - ceil($pow * $value))) / $pow;
    }
}
