<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobWorkerChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $job, $shiftsInHour, $old_job_data, $oldWorker;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($job, $shiftsInHour, $old_job_data, $oldWorker)
    {
        $this->job = $job;
        $this->shiftsInHour = $shiftsInHour;
        $this->old_job_data = $old_job_data;
        $this->oldWorker = $oldWorker;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
