<?php

namespace App\Listeners;

// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Queue\InteractsWithQueue;

use App\Events\VideoUploadEvent;
use App\Jobs\UploadToStream;
use App\Models\Media;
use App\Models\Upload;
use App\Services\CloudService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VideoUploadEventListener
{
    public $uploadId;
    public $file;
    public $cloudService ;
    // /**
    //  * Create the event listener.
    //  */
    public function __construct(CloudService $cloudService)
    {
        $this->cloudService = $cloudService;
    }

    /**
     * Handle the event.
     */
    public function handle(VideoUploadEvent $event ): void
    {
        
        Log::info('Message de log 4');

        UploadToStream::dispatch($event->upload ,$event->path, $event->sourceModel);

    }
}
