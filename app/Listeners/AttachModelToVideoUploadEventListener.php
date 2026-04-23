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
    public CloudService $cloudService ;
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
        
        AttachModelToVideoUploaded::dispatch($event->upload_uuid ,$event->model);
    
    }
}
