<?php

namespace App\Listeners;

use App\Repositories\UploadRepository;
use App\Services\CloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $cacheUpload = $this->uploadRepository->getByUuid($event->upload_uuid);
       
        $mediaItem = null;
        $attempts = 0;
        $maxAttempts = 10;

        while ($mediaItem === null && $attempts < $maxAttempts) {
            $mediaItem = $cacheUpload->getMedia('*')->first();

            if ($mediaItem === null) {
                sleep(5); // attendre 1 seconde
                $attempts++;
            }
        }

        if ($mediaItem) {
            $mediaItem->copy($event->model, $event->collection, $event->disk);
        }
    
    }
}
