<?php

namespace App\Jobs;

use App\Events\AttachModelToVideoUploadEvent;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadToStream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60];


    public function __construct(public Upload $upload, public string $path ,  public ?Model $model = null )
    {
        $this->onQueue('upload');
    }

    public function handle( CloudService $cloudService)
    {
      

        try {
            Log::info('Message de log 5');

            $upload = $this->upload;

            if (!$upload) return;

            // $upload->status = 'uploading';
            // $upload->pct_complete = 0;
            // $upload->save();

            $fullPath = Storage::disk('public')->path($this->path);
           
            $result = $cloudService->uploadVideo(
                new \Illuminate\Http\File($fullPath)
            );

            $streamUid = $result['uid'];

            Log::info('Message de log 11*');


            $media = $upload->addMedia($fullPath)
                ->usingFileName(basename($fullPath))
                ->withCustomProperties([
                    'uuid' => $upload->uuid,
                    // 'user_id' => $upload->user_id,
                    'stream_uid' => $streamUid
                ])
                ->toMediaCollection('cloudmedia', config('filesystems.cloud'));


            if( !($this->model instanceof Upload))   event(new AttachModelToVideoUploadEvent($upload->uuid, $this->model));
;

            // $upload->status = 'processing'; // en attente encodage
            // $upload->pct_complete = 1; // juste pour indiquer upload terminé
            // $upload->save();

        } catch (\Exception $e) {

            Log::error('FAIL:1'. $e->getMessage() , [
                 'trace' => $e->getTraceAsString()
            ]);

            // $upload->status = 'error';
            $upload->pct_complete = 0;
            $upload->save();
        }

         // Une fois terminé, supprimer le fichier local
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    
}
