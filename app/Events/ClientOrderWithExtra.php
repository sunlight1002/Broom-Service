<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientOrderWithExtra
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $client, $order, $extra;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($client, $order, $extra)
    {
        $this->client = $client;
        $this->order = $order;
        $this->extra = $extra;
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
