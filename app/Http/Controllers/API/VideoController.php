<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class VideoController extends Controller
{
    public function upload(Request $request)
    {
        $account_id = config('services.cloudflare.account_id'); // ton account_id
        $api_token  = config('services.cloudflare.token');      // ton API Token Cloudflare

        // 1️⃣ Vérifier si le token est actif
        $verifyToken = Http::withToken($api_token)
            ->get("https://api.cloudflare.com/client/v4/user/tokens/verify");

        if (!$verifyToken->json('success')) {
            return response()->json([
                'error' => 'Token invalide ou expiré',
                'details' => $verifyToken->json()
            ], 401);
        }

        // 2️⃣ Vérifier l’accès au compte Cloudflare Stream
        $checkStream = Http::withToken($api_token)
            ->get("https://api.cloudflare.com/client/v4/accounts/$account_id/stream");

        if (!$checkStream->json('success')) {
            return response()->json([
                'error' => 'Token non autorisé pour ce compte Stream',
                'details' => $checkStream->json()
            ], 401);
        }

        // 3️⃣ Vérifier le fichier uploadé
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Aucun fichier détecté'], 400);
        }

        $video = $request->file('file');

        // 4️⃣ Upload de la vidéo sur Cloudflare Stream
        $upload = Http::withToken($api_token)
            ->attach('file', file_get_contents($video), $video->getClientOriginalName())
            ->post("https://api.cloudflare.com/client/v4/accounts/$account_id/stream");

        $result = $upload->json();

        if (isset($result['result']['uid'])) {
            return response()->json([
                'success' => true,
                'video_uid' => $result['result']['uid']
            ]);
        }

        // 5️⃣ En cas d'erreur Cloudflare
        return response()->json([
            'error' => 'Échec de l’upload',
            'details' => $result
        ], $upload->status());
    }
}
