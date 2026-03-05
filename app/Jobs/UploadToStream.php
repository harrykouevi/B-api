<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\Upload;
use App\Services\CloudService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UploadToStream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uploadId;
    public $path;
    public ?Model $model = null;

    public function __construct($uploadId, $path , ?Model $model = null )
    {
        $this->uploadId = $uploadId;
        $this->path = $path;
        $this->model = $model;
    }

    public function handle(CloudService $cloudService)
    {
        $upload = Upload::find($this->uploadId);

        if (!$upload) return;

        // $upload->status = 'uploading';
        // $upload->pct_complete = 0;
        // $upload->save();

        $fullPath = Storage::disk('public')->path($this->path);

        try {

            // Nom fichier
            $filename = basename($fullPath);
            // Mime type
            $mimeType = mime_content_type($fullPath);
            // Taille
            $size = filesize($fullPath);

            $result = $cloudService->uploadVideo(
                new \Illuminate\Http\File($fullPath)
            );

            // $upload->stream_uid = $result['uid'];
            // $upload->status = 'processing'; // en attente encodage
            // $upload->pct_complete = 1; // juste pour indiquer upload terminé
            // $upload->save();


            // Créer l'enregistrement media
            $media = Media::create([
                'model_type' => get_class($upload),  // ton modèle parent
                'model_id'   => $upload->id,
                'uuid'       => $upload->uuid,
                'collection_name' => 'cloudmedia',
                'name'       => pathinfo($filename, PATHINFO_FILENAME), 
                'file_name'  => $filename,
                'mime_type'  => $mimeType,
                'disk'       => config('filesystems.cloud'),
                'size'       => $size,
                'custom_properties' => ['uuid'=> $upload->uuid,'user_id' => Auth::id() , 'stream_uid' => $result['uid']],
                'manipulations' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
                
            ]);

            if( !($this->model instanceof Upload))  $media->copy($this->model, 'cloudmedia');

        } catch (\Exception $e) {
            $upload->status = 'error';
            $upload->pct_complete = 0;
            $upload->save();
        }

         // Une fois terminé, supprimer le fichier local
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    
}
