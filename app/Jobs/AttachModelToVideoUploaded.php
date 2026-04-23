<?php

namespace App\Jobs;

use App\Events\CloudMediaIsReadyEvent;
use App\Repositories\UploadRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AttachModelToVideoUploaded implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60];

    public $upload_uuId;
    public $path;
    public ?Model $model = null;

    public function __construct( $upload_uuId, ?Model $model = null )
    {
        $this->onQueue('upload');
        $this->upload_uuId = $upload_uuId;
        $this->model = $model;
    }

    /**
     * Execute the job.
     */
    public function handle( UploadRepository $uploadRepository): void
    {
        
        try {
            $cacheUpload = $uploadRepository->getByUuid($this->upload_uuId);
            if (!$cacheUpload) {
                throw new \Exception('Upload not found');
            }
            Log::info('Message de log 1+2');
           
            $media = $cacheUpload->getMedia('*')->first();
            $streamUid = $media->getCustomProperty('stream_uid');
            if($streamUid){

                Log::info('is streamer', [
                    $streamUid
                ]);

                $url = "https://customer-jhmjx2xxk4rdo62d.cloudflarestream.com/{$streamUid}/manifest/video.m3u8";
                $response = Http::head($url);

                if ($response->status() !== 200) {
                    Log::info('Manifest not ready, will retry...');
                    throw new \Exception('Manifest not ready'); // Laravel retry automatiquement
                }
                
            }else{
                Log::info($media->url);
                $response = Http::head($media->url );

                if (!$response->successful()) {
                    Log::info('Manifest not ready, will retry...');
                    
                    throw new \Exception('Manifest not ready'); // Laravel retry automatiquement
                }
            }
            
            Log::info('Media is ready', [
                'media_id' => $media->id,
                'model_id' => $this->model?->id
            ]);

            event(new CloudMediaIsReadyEvent($this->model));

            $media->copy($this->model, 'cloudmedia', config('filesystems.cloud'));

        } catch (\Exception $e) {
            Log::error('FAIL: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'upload_uuid' => $this->upload_uuId,
                'model_id' => $this->model?->id ?? null
            ]);

            // relance l'exception pour que Laravel retry
            throw $e;
        }
    }
}
