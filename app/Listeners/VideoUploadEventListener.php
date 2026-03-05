<?php

namespace App\Listeners;

// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\UploadToStream;
use App\Models\Media;
use App\Models\Upload;
use App\Services\CloudService;
use Illuminate\Support\Facades\Auth;


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
    public function handle(object $event ): void
    {
        

        UploadToStream::dispatch($event->uploadId ,$event->path, $event->sourceModel);

        
        // $upload = Upload::find($this->uploadId);

        // if (!$upload) return;

        // // $upload->status = 'uploading';
        // // $upload->pct_complete = 0;
        // $upload->save();

        // // try {
        //     $filename = $this->file->getClientOriginalName();

        //     $result = $this->cloudService->upload($this->file);

        //     // $upload->stream_uid = $result['uid'];
        //     // $upload->status = 'processing'; // en attente encodage
        //     // $upload->pct_complete = 1; // juste pour indiquer upload terminé
        //     $upload->save();


        //     // Créer l'enregistrement media
        //     $media = Media::create([
        //         'model_type' => get_class($upload),  // ton modèle parent
        //         'model_id'   => $upload->id,
        //         'uuid'       => $upload->uuid,
        //         'collection_name' => 'cloudmedia',
        //         'name'       => pathinfo($filename, PATHINFO_FILENAME),
        //         'file_name'  => $filename,
        //         'mime_type'  => $this->file->getClientMimeType(),
        //         'disk'       => config('filesystems.cloud'),
        //         'size'       => $this->file->getSize(),
        //         'custom_properties' => json_encode(['uuid'=> $upload->uuid,'user_id' => Auth::id()]),
        //         'manipulations' => json_encode([]),
        //         'generated_conversions' => json_encode([]),
        //         'responsive_images' => json_encode([]),
                
        //     ]);
                    
           

        // // } catch (\Exception $e) {
        // //     $upload->status = 'error';
        // //     $upload->pct_complete = 0;
        // //     $upload->save();
        // // }
    }
}
