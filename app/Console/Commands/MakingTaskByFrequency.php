<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskManagement;
use App\Models\ServiceSchedule;
use App\Models\ManageTime;
use Carbon\Carbon;
use App\Traits\JobSchedule; // Import the trait

class MakingTaskByFrequency extends Command
{
    use JobSchedule;

    protected $signature = 'making:task';

    protected $description = 'Generate tasks based on frequency and repeatancy';

    public function handle()
    {
        $today = Carbon::today();

        // Fetch tasks where `next_start_date` is today or overdue
        $tasks = TaskManagement::whereDate('next_start_date', '<=', $today)
            ->whereNotNull('next_start_date')
            ->get();

        foreach ($tasks as $task) {
            // Check if the task can be created
            if ($task->repeatancy === 'until_date' && $task->until_date) {
                $untilDate = Carbon::parse($task->until_date);
                if ($today->gt($untilDate)) {
                    continue;
                }
            }

            $frequency_id = $task->frequency_id;
            $service = ServiceSchedule::where('id', $frequency_id)
                ->where('status', 1)
                ->first();

            $manageTime = ManageTime::first();
            $workingWeekDays = json_decode($manageTime->days);
            $repeat_value = $service->period;
            $cycle = $service->cycle;

            if ($cycle > 1) {
                if ($task->cycle_counter < $cycle - 1) {
                    // Create intermediate task
                    $newTask = TaskManagement::create([
                        'phase_id' => $task->phase_id,
                        'task_name' => $task->task_name,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'description' => $task->description,
                        'due_date' => $today,
                        'user_id' => $task->user_id,
                        'user_type' => $task->user_type,
                        'frequency_id' => $task->frequency_id,
                        'repeatancy' => $task->repeatancy,
                        'until_date' => $task->until_date,
                        'next_start_date' => $today->addDay(), // Next day for intermediate tasks
                        'cycle_counter' => $task->cycle_counter + 1, // Increment cycle counter
                    ]);

                    \Log::info("Intermediate task created: ", $newTask->toArray());
                } else {
                    // Create last task in the cycle
                    $next_start_date = $this->scheduleNextJobDate(
                        $task->next_start_date,
                        $repeat_value,
                        strtolower(Carbon::parse($task->next_start_date)->format('l')),
                        $workingWeekDays
                    );

                    $newTask = TaskManagement::create([
                        'phase_id' => $task->phase_id,
                        'task_name' => $task->task_name,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'description' => $task->description,
                        'due_date' => $today,
                        'user_id' => $task->user_id,
                        'user_type' => $task->user_type,
                        'frequency_id' => $task->frequency_id,
                        'repeatancy' => $task->repeatancy,
                        'until_date' => $task->until_date,
                        'next_start_date' => $next_start_date, // Based on scheduling logic
                        'cycle_counter' => 0, // Reset cycle counter
                    ]);

                    \Log::info("Final task created: ", $newTask->toArray());
                }
            } else {
                // For tasks without a cycle
                $next_start_date = $this->scheduleNextJobDate(
                    $task->next_start_date,
                    $repeat_value,
                    strtolower(Carbon::parse($task->next_start_date)->format('l')),
                    $workingWeekDays
                );

                $newTask = TaskManagement::create([
                    'phase_id' => $task->phase_id,
                    'task_name' => $task->task_name,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'description' => $task->description,
                    'due_date' => $today,
                    'user_id' => $task->user_id,
                    'user_type' => $task->user_type,
                    'frequency_id' => $task->frequency_id,
                    'repeatancy' => $task->repeatancy,
                    'until_date' => $task->until_date,
                    'next_start_date' => $next_start_date, // Based on scheduling logic
                    'cycle_counter' => 0, // Reset cycle counter
                ]);

                \Log::info("Task created: ", $newTask->toArray());
            }

            // Update the old task: reset its frequency and cycle counter
            $task->update([
                'frequency_id' => null,
                'repeatancy' => null,
                'until_date' => null,
                'next_start_date' => null,
                'cycle_counter' => null,
            ]);
        }

        $this->info('Task generation process completed.');
        return 0;
    }
}
