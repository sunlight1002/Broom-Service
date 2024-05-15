<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobNotificationToClient
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $worker;
    public $client;
    public $job;
    public $emailData;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($worker, $client, $job, $emailData)
    {
        $this->worker = $worker;
        $this->client = $client;
        $this->job = $job;
        $this->emailData = $emailData;
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
