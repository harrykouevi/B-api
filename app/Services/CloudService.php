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

class CloudService{

    public function sendImage($image ){
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

        // Exemple : enregistrer en base
        // Image::create([
        //     'cf_id' => $data['id'],
        //     'url' => $data['variants'][0],
        // ]);

        return [
            'message' => 'Image uploadée avec succès',
            'cloudflare_id' => $data['id'],
            'url' => $data['variants'][0],
        ];
    }
}