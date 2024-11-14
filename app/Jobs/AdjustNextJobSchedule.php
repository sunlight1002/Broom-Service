<?php

namespace App\Jobs;

use App\Models\Job; // Ensure you import the Job model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\JobSchedule;

class AdjustNextJobSchedule implements ShouldQueue
{
    use Dispatchable, JobSchedule, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $jobData;
    protected $date;
    protected $preferredWeekDay;
    protected $workingWeekDays;
    protected $repeat_value;
    protected $jobModel; // Renamed from $job to $jobModel
    protected $old_job_data;
    protected $repeatancy;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data, array $jobData, $date, $preferredWeekDay, $workingWeekDays, $repeat_value, Job $job, $old_job_data, $repeatancy)
    {
        $this->data = $data;
        $this->jobData = $jobData;
        $this->date = $date;
        $this->preferredWeekDay = $preferredWeekDay;
        $this->workingWeekDays = $workingWeekDays;
        $this->repeat_value = $repeat_value;
        $this->jobModel = $job; // Update the assignment
        $this->old_job_data = $old_job_data;
        $this->repeatancy = $repeatancy;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $jobDate = $this->date;
        $old_job_data = $this->old_job_data;

        $jobsToUpdate = Job::where('parent_job_id', $this->jobModel->parent_job_id)
                ->where('id', '!=', $this->jobModel->id)
                ->when($this->repeatancy === 'until_date', function ($query) {
                    return $query->whereDate('start_date', '<=', $this->data['until_date']);
                })
                ->when($this->repeatancy === 'forever', function ($query) use ($jobDate) {
                    return $query->where('start_date', '>=', $jobDate);
                })
                ->orderBy('start_date', 'asc')
                ->get();

        if ($old_job_data['start_date'] == $jobDate) {
            foreach ($jobsToUpdate as $jobToUpdate) {
                $jobToUpdate->update($this->jobData);
            }
        } else {
            foreach ($jobsToUpdate as $jobToUpdate) {
                // Schedule the next job date
                $nextJobDate = $this->scheduleNextJobDate($jobDate, $this->repeat_value, $this->preferredWeekDay, $this->workingWeekDays);
        
                // Update the job with the new dates
                $jobToUpdate->update(array_merge($this->jobData, [
                    'start_date' => $jobDate,
                    'next_start_date' => $nextJobDate
                ]));
        
                // Update jobDate for the next iteration
                $jobDate = $nextJobDate;
        
                // Break the loop if repeatancy is until_date and the date exceeds the limit
                if ($this->repeatancy === 'until_date' && $jobDate > $this->data['until_date']) {
                    break;
                }
            }
        }
    }
}
