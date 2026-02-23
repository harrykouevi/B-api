<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;

    /**
     * C'est ici qu'on réceptionne le commentaire
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Pas besoin de toucher à broadcastOn sauf si tu fais du temps réel (Socket.io/Pusher)
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}