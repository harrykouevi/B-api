<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model ;


class VideoUploadEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $uploadId;
    public string $path;
    public Model $sourceModel ;

    public function __construct($uploadId, $path , $sourceModel)
    {
        $this->uploadId = $uploadId;
        $this->path = $path;
        $this->sourceModel = $sourceModel ;

    }

   
}
