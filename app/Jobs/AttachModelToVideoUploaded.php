<?php

namespace App\Jobs;

use App\Repositories\UploadRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AttachModelToVideoUploaded implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uploadId;
    public $path;
    public ?Model $model = null;
    public  UploadRepository $uploadRepository ;

    public function __construct( UploadRepository $uploadRepository , $uploadId, ?Model $model = null )
    {
        $this->onQueue('upload');
        $this->uploadRepository = $uploadRepository; 
        $this->uploadId = $uploadId;
        $this->model = $model;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cacheUpload = $this->uploadRepository->getByUuid($this->uploadId);
       
        $mediaItem = null;
        $attempts = 0;
        $maxAttempts = 10;

        while ($mediaItem === null ) {
            $mediaItem = $cacheUpload->getMedia('*')->first();

            if ($mediaItem === null) {
                sleep(5); // attendre 1 seconde
                
            }
        }

        if ($mediaItem) {
            $mediaItem->copy($this->model, 'cloudmedia', config('filesystems.cloud'));
        }
    }
}
