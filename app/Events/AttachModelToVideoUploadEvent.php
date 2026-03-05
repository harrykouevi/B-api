<?php

namespace App\Events;

use App\Models\Upload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttachModelToVideoUploadEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

   
    public $upload_uuid;
    public Model $model;
    public string $collection ;
    public string $disk ;
    public mixed $file ;

    public function __construct($upload_uuid, $model)
    {
        $this->upload_uuid = $upload_uuid;
        $this->model = $model;
        $this->collection = 'cloudmedia' ;
        $this->disk = config('filesystems.cloud') ;
    }

}
