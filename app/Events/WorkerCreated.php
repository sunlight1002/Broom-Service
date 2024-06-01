<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkerCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $worker;
    public $type;
    public $formId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($worker, $type = NULL, $formId = NULL)
    {
        $this->worker = $worker;
        $this->type = $type;
        $this->formId = $formId;
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
