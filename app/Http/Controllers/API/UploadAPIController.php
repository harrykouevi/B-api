<?php
/*
 * File name: UploadAPIController.php
 * Last modified: 2024.04.10 at 14:47:27
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadRequest;
use App\Models\Upload;
use App\Repositories\UploadRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Prettus\Validator\Exceptions\ValidatorException;

class UploadAPIController extends Controller
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

    /**
     * @param UploadRequest $request
     * @return JsonResponse
     */
    public function store(UploadRequest $request): JsonResponse
    {
        $input = $request->all();
        // Gestion de l'exception
        Log::channel('listeners_transactions')->error('Erreur at upload files #' , [
            'exception' => $request->all(),
        ]);
        try {
            $upload = $this->uploadRepository->create($input);
            $upload->addMedia($input['file'])
                ->withCustomProperties(['uuid' => $input['uuid'], 'user_id' => auth()->id()])
                ->toMediaCollection($input['field']);
            return $this->sendResponse($input['uuid'], "Uploaded Successfully");
        } catch (ValidatorException $e) {
            return $this->sendError(false, $e->getMessage());
        }
    }

    /**
     * clear cache from Upload table
     */
    /**
     * clear cache from Upload table
     */
    public function clear(Request $request): JsonResponse
    {
        $input = $request->all();
        if (!$request->has('uuid')) {
            return $this->sendResponse(false, 'Media not found');
        }

        try {
            if (is_array($input['uuid'])) {
                $result = $this->uploadRepository->clearWhereIn($input['uuid']);
            } else {
                $result = $this->uploadRepository->clear($input['uuid']);

                // Vérifier si la suppression a réussi
                if ($result === false) {
                    return $this->sendResponse(false, 'Media not found or already deleted');
                }
            }

            return $this->sendResponse($result, 'Media deleted successfully');
        } catch (Exception $e) {
            Log::error('Error in clear method: ' . $e->getMessage());
            return $this->sendResponse(false, 'Error when delete media');
        }
    }


    /**
     * Delete media by URL
     * @param string $imageUrl
     * @return JsonResponse
     */
    public function deleteByUrl(Request $request): JsonResponse
    {
        try {
            $imageUrl = $request->input('image_url');

            if (!$imageUrl) {
                return $this->sendResponse(false, 'Image URL is required');
            }

            Log::info('Searching for upload by URL: ' . $imageUrl);

            // Décoder l'URL si elle est encodée
            $imageUrl = urldecode($imageUrl);

            // Extraire le nom de fichier de l'URL
            $fileName = basename($imageUrl);
            Log::info('Extracted filename: ' . $fileName);

            // Méthode 1: Chercher par Upload avec ses médias
            $upload = Upload::whereHas('media', function($query) use ($fileName) {
                $query->where('file_name', 'like', '%' . $fileName . '%')
                    ->orWhere('name', 'like', '%' . $fileName . '%');
            })->first();

            if ($upload) {
                Log::info('Found upload by media URL, UUID: ' . $upload->uuid);

                // Créer une requête pour passer à la méthode clear
                $clearRequest = new Request(['uuid' => $upload->uuid]);
                return $this->clear($clearRequest);
            }

            // Méthode 2: Chercher directement les Media orphelins
            $medias = App\Models\Media::where('file_name', 'like', '%' . $fileName . '%')
                ->orWhere('name', 'like', '%' . $fileName . '%')
                ->get();

            $deletedCount = 0;
            foreach ($medias as $media) {
                // Supprimer physiquement les fichiers
                $mediaPath = storage_path('app/public/' . $media->id);
                if (file_exists($mediaPath)) {
                    try {
                        \File::deleteDirectory($mediaPath);
                        Log::info('Physical media directory deleted: ' . $mediaPath);
                    } catch (Exception $e) {
                        Log::warning('Could not delete physical directory: ' . $mediaPath . ' - ' . $e->getMessage());
                    }
                }

                // Supprimer de la base de données
                $media->delete();
                $deletedCount++;
                Log::info('Orphaned media deleted from database: ' . $media->id);
            }

            if ($deletedCount > 0) {
                Log::info('Deleted ' . $deletedCount . ' orphaned media entries');
                return $this->sendResponse(true, 'Media deleted successfully');
            }

            // Méthode 3: Extraire l'ID du chemin et vérifier
            if (preg_match('/\/public\/(\d+)\//', $imageUrl, $matches)) {
                $id = $matches[1];
                $upload = Upload::find($id);
                if ($upload) {
                    $clearRequest = new Request(['uuid' => $upload->uuid]);
                    return $this->clear($clearRequest);
                }
            }

            Log::info('No media found for URL: ' . $imageUrl);
            return $this->sendResponse(false, 'No media found for URL');
        } catch (Exception $e) {
            Log::error('Error in deleteByUrl: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine());
            return $this->sendResponse(false, 'Error deleting media by URL: ' . $e->getMessage());
        }
    }

    /**
     * Extract ID from image URL
     * @param string $url
     * @return string|null
     */
    private function extractIdFromUrl(string $url): ?string
    {
        try {
            // Pattern pour votre structure d'URL: /storage/app/public/1368/conversions/...
            if (preg_match('/\/public\/(\d+)\//', $url, $matches)) {
                return $matches[1];
            }

            // Pattern alternatif si la structure est différente
            if (preg_match('/\/(\d+)\/conversions\//', $url, $matches)) {
                return $matches[1];
            }

            Log::info('No ID pattern found in URL: ' . $url);
            return null;
        } catch (Exception $e) {
            Log::error('Error extracting ID from URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete physical file from storage
     * @param string $url
     * @return bool
     */
    private function deletePhysicalFile(string $url): bool
    {
        try {
            // Extraire le chemin relatif depuis l'URL
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '';

            // Convertir l'URL publique vers le chemin de stockage
            // Ex: /storage/app/public/1368/... -> app/public/1368/...
            $storagePath = str_replace('/storage/', '', $path);

            if (file_exists(storage_path($storagePath))) {
                unlink(storage_path($storagePath));
                Log::info('Physical file deleted: ' . $storagePath);
                return true;
            }

            // Essayer aussi le chemin public
            $publicPath = public_path($path);
            if (file_exists($publicPath)) {
                unlink($publicPath);
                Log::info('Public file deleted: ' . $publicPath);
                return true;
            }

            Log::info('Physical file not found: ' . $storagePath);
            return false;

        } catch (Exception $e) {
            Log::error('Error deleting physical file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete by path (alternative endpoint)
     * @param string $path
     * @return JsonResponse
     */
    /**
     * Delete by path (alternative endpoint)
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteByPath(Request $request): JsonResponse
    {
        try {
            $path = $request->input('path');

            if (!$path) {
                return $this->sendResponse(false, 'Path is required');
            }

            // Extraire le nom de fichier du chemin
            $fileName = basename($path);

            // Chercher l'upload qui correspond à ce chemin
            $upload = Upload::whereHas('media', function($query) use ($path, $fileName) {
                $query->where('uuid', 'like', '%' . $fileName . '%');
            })->first();

            if ($upload) {
                // Créer une requête pour passer à la méthode clear
                $clearRequest = new Request(['uuid' => $upload->uuid]);
                return $this->clear($clearRequest);
            }

            return $this->sendResponse(false, 'No upload found for path');
        } catch (Exception $e) {
            Log::error('Error in deleteByPath: ' . $e->getMessage());
            return $this->sendResponse(false, 'Error deleting media by path');
        }
    }

    public function findByUuid(string $uuid)
    {
        try {
            return Upload::where('uuid', $uuid)->first();
        } catch (Exception $e) {
            Log::error('Error finding upload by UUID: ' . $e->getMessage());
            return null;
        }
    }
}
