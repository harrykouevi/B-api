<?php

namespace App\Events;

use App\Models\Upload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model ;
use Illuminate\Support\Facades\Log;

class VideoUploadEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct( 
        public Upload $upload, 
        public string $path , 
        public Model $sourceModel)
    {
        Log::info('Message de log 3');
        // $this->uploadId = $uploadId;
        // $this->path = $path;
        // $this->sourceModel = $sourceModel ;

    }

   
}
