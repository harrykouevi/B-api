<?php

namespace App\Listeners;

use App\Jobs\AttachModelToVideoUploaded;
use App\Repositories\UploadRepository;
use App\Services\CloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AttachModelToVideoUploadEventListener
{
    public $cloudService ;
    public UploadRepository $uploadRepository ;
    // /**
    //  * Create the event listener.
    //  */
     public function __construct(CloudService $cloudService ,UploadRepository $uploadRepository)
    {
        $this->cloudService = $cloudService;
        $this->uploadRepository = $uploadRepository ;
    }

    /**
     * Handle the event.
     */
    public function handle(object $event ): void
    {    
        // $cacheUpload = $this->uploadRepository->getByUuid($event->upload_uuid);
        // $media = $cacheUpload->getMedia('*')->first();
        // $streamUid = $media->getCustomProperty('stream_uid');
        Log::info('Message de log 1+1');

        AttachModelToVideoUploaded::dispatch($event->upload_uuid ,$event->model);
 
       
        // $mediaItem = null;
        // $attempts = 0;
        // $maxAttempts = 10;

        // while ($mediaItem === null ) {
        //     $mediaItem = $cacheUpload->getMedia('*')->first();

        //     if ($mediaItem === null) {
        //         sleep(5); // attendre 1 seconde
                
        //     }
        // }

        // if ($mediaItem) {
        //     $mediaItem->copy($event->model, $event->collection, $event->disk);
        // }
    
    }
}
