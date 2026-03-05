<?php
/*
 * File name: CloudService.php
 * Last modified: 2026.02.25 at 15:37:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2026
 */

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudService{


    public function uploadVideo($file)
    {
        $response = Http::withToken(config('services.cloudflare.stream_token'))
            ->attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                basename($file->getRealPath())
            )
            ->post("https://api.cloudflare.com/client/v4/accounts/"
                . config('services.cloudflare.account_id')
                . "/stream");

         Log::error('FAIL:' , [
                 $response
            ]);
        if (!$response->successful()) {
            throw new \Exception('Stream upload failed');
        }

        return $response->json()['result'];
    }


    public function sendImagexxxx($image ){
         $response = Http::withToken(env('CLOUDFLARE_API_TOKEN'))
        ->attach(
            'file',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )
        ->post("https://api.cloudflare.com/client/v4/accounts/"
            . env('CLOUDFLARE_ACCOUNT_ID')
            . "/images/v1");

         if (!$response->successful()) {
            return response()->json([
                'error' => 'Upload failed',
                'details' => $response->json()
            ], 500);
        }

        $data = $response->json()['result'];

        return [
            'message' => 'Image uploadée avec succès',
            'cloudflare_id' => $data['id'],
            'url' => $data['variants'][0],
        ];
    }
}