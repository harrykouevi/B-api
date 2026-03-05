<?php
/*
 * File name: UploadController.php
 * Last modified: 2024.04.10 at 14:47:28
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Events\VideoUploadEvent;
use App\Http\Requests\UploadRequest;
use App\Repositories\UploadRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Prettus\Validator\Exceptions\ValidatorException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
 use Illuminate\Support\Str;
 use App\Models\Media;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    private UploadRepository $uploadRepository;

    /**
     * UploadController constructor.
     * @param UploadRepository $uploadRepository
     */
    public function __construct(UploadRepository $uploadRepository)
    {
        parent::__construct();
        $this->uploadRepository = $uploadRepository;
    }

    public function index():View
    {
        return view('medias.index');
    }

    /**
     * Get images paths
     * @param $id
     * @param $conversion
     * @param null $filename
     * @return BinaryFileResponse
     */
    /*public function storage($id, $conversion, $filename = null): BinaryFileResponse
    {
        $array = explode('.', $conversion . $filename);
        $extension = strtolower(end($array));
        if (isset($filename)) {
            return response()->file(storage_path('app/public/' . $id . '/' . $conversion . '/' . $filename));
        } else {
            $filename = $conversion;
            return response()->file(storage_path('app/public/' . $id . '/' . $filename));
        }

    }*/

    public function storage($id, $conversion, $filename = null): BinaryFileResponse|\Illuminate\Http\Response
    {
        $array = explode('.', $conversion . $filename);
        $extension = strtolower(end($array));

        if (isset($filename)) {
            $filePath = storage_path('app/public/' . $id . '/' . $conversion . '/' . $filename);
        } else {
            $filename = $conversion;
            $filePath = storage_path('app/public/' . $id . '/' . $filename);
        }

        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            // Option 1: Retourner une image par défaut
            $defaultImagePath = public_path('images/image-not-found.jpg');
            if (file_exists($defaultImagePath)) {
                return response()->file($defaultImagePath);
            }

            // Option 2: Retourner une réponse 404 si aucune image par défaut
            return response('Fichier inexistant', 404);
        }

        return response()->file($filePath);
    }

    public function collectionsNames(UploadRequest $request): JsonResponse
    {
        $allMedias = $this->uploadRepository->collectionsNames();
        return $this->sendResponse($allMedias, 'Get Collections Successfully');
    }

    /**
     * Store a newly created Upload in storage.
     * @param UploadRequest $request
     * @return JsonResponse
     */
    public function store(UploadRequest $request): JsonResponse
    {
        try {
            
            $file = $request->file('file');
            Log::info('Message de log 1');
            $upload = $this->uploadRepository->createWithMedia($file,$request->all());
            return $this->sendResponse($upload->uuid, "Uploaded Successfully");

        } catch (ValidatorException $e) {
            return $this->sendResponse(false, $e->getMessage());
        }
    }

    public function all(UploadRequest $request, $collection = null): string|bool
    {
        $allMedias = $this->uploadRepository->allMedia($collection);
        if (!auth()->user()->hasRole('admin')) {
            $allMedias = $allMedias->filter(function ($element) {
                if (isset($element['custom_properties']['user_id'])) {
                    return $element['custom_properties']['user_id'] == auth()->id();
                }
                return false;
            });
        }
        return $allMedias->toJson();
    }


    /**
     * Retourne le status et la progression d'une vidéo pour le polling.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function getStatus(Request $request, string $uuid): JsonResponse
    {
        $upload = Upload::where('uuid', $uuid)->first();

        if (!$upload) {
            return response()->json([
                'success' => false,
                'message' => 'Upload not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'uuid' => $upload->uuid,
            'status' => $upload->status,             // pending / uploading / processing / ready / error
            'pct_complete' => $upload->pct_complete, // 0 → 100
            'stream_uid' => $upload->stream_uid,     // utile pour le player Stream
            'type' => $upload->type                  // video / image
        ]);
    }

    /**
     * clear cache from Upload table
     * @throws \Exception
     */
    public function clear(UploadRequest $request): JsonResponse
    {
        $input = $request->all();
        if ($input['uuid']) {
            $result = $this->uploadRepository->clear($input['uuid']);
            return $this->sendResponse($result, 'Media deleted successfully');
        }
        return $this->sendResponse(false, 'Error will delete media');

    }

    /**
     * clear all cache
     * @return RedirectResponse
     */
    public function clearAll(): RedirectResponse
    {
        $this->uploadRepository->clearAll();
        return redirect()->back();
    }
}
