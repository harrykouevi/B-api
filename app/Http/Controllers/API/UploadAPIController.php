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
    public function clear(UploadRequest $request): JsonResponse
    {
        $input = $request->all();
        if (! $request->has('uuid')) {
            return $this->sendResponse(false, 'Media not found');
        }
        try {
            if (is_array($input['uuid'])) {
                $result = $this->uploadRepository->clearWhereIn($input['uuid']);
            } else {
                $result = $this->uploadRepository->clear($input['uuid']);
            }
            return $this->sendResponse($result, 'Media deleted successfully');
        } catch (Exception) {
            return $this->sendResponse(false, 'Error when delete media');
        }
    }

    /**
     * Delete media by URL
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteByUrl(Request $request): JsonResponse
    {
        try {
            $imageUrl = $request->input('image_url');

            if (empty($imageUrl)) {
                return $this->sendError(false, 'Image URL is required');
            }

            Log::info('Attempting to delete image by URL: ' . $imageUrl);

            // Méthode 1: Extraire l'ID depuis l'URL
            $extractedId = $this->extractIdFromUrl($imageUrl);
            if ($extractedId) {
                Log::info('Extracted ID from URL: ' . $extractedId);
                $result = $this->uploadRepository->clear($extractedId);
                if ($result) {
                    return $this->sendResponse(true, 'Media deleted successfully using extracted ID');
                }
            }

            // Méthode 2: Chercher dans la base de données par URL
            $result = $this->uploadRepository->deleteByUrl($imageUrl);
            if ($result) {
                return $this->sendResponse(true, 'Media deleted successfully using URL lookup');
            }

            // Méthode 3: Supprimer le fichier physique directement
            $physicalDeleteResult = $this->deletePhysicalFile($imageUrl);
            if ($physicalDeleteResult) {
                return $this->sendResponse(true, 'Physical file deleted successfully');
            }

            return $this->sendError(false, 'Media not found or could not be deleted');

        } catch (Exception $e) {
            Log::error('Error deleting image by URL: ' . $e->getMessage(), [
                'url' => $request->input('image_url'),
                'exception' => $e
            ]);
            return $this->sendError(false, 'Error when deleting media: ' . $e->getMessage());
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
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteByPath(Request $request): JsonResponse
    {
        try {
            $path = $request->query('path');

            if (empty($path)) {
                return $this->sendError(false, 'Path is required');
            }

            Log::info('Attempting to delete by path: ' . $path);

            // Supprimer le fichier physique
            $storagePath = str_replace('/storage/', '', $path);
            if (file_exists(storage_path($storagePath))) {
                unlink(storage_path($storagePath));

                // Essayer aussi de supprimer de la DB
                $this->uploadRepository->deleteByPath($path);

                return $this->sendResponse(true, 'File deleted successfully');
            }

            return $this->sendError(false, 'File not found');

        } catch (Exception $e) {
            Log::error('Error deleting by path: ' . $e->getMessage());
            return $this->sendError(false, 'Error when deleting file: ' . $e->getMessage());
        }
    }
}