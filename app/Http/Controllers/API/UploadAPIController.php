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
use App\Models\Media;
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
            return $this->sendResponse(false, 'UUID parameter is required');
        }

        try {
            $identifier = $input['uuid'];

            // Vérifier si c'est un UUID, un ID numérique ou une URL
            $upload = null;

            // Si c'est un format UUID standard
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
                $upload = Upload::where('uuid', $identifier)->first();
                // $upload = Upload::where('id', $identifier)->first();
            }
            // Si c'est un ID numérique
            else if (is_numeric($identifier)) {
                Log::info('Upload deletion with id : ' . $identifier);
                $identifier = (int) $identifier ;
                $upload = Upload::find($identifier);
            }
            // Si c'est une URL, essayer d'extraire l'UUID ou l'ID
            else if (filter_var($identifier, FILTER_VALIDATE_URL)) {
                // Essayer d'extraire l'ID numérique de l'URL
                if (preg_match('/\/public\/(\d+)\//', $identifier, $matches)) {
                    $id = $matches[1];
                    $upload = Upload::find($id);
                }

                // Si pas trouvé, essayer d'extraire l'UUID de l'URL
                if (!$upload) {
                    // Pattern pour trouver un UUID dans une URL
                    if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $identifier, $uuidMatches)) {
                        $upload = Upload::where('uuid', $uuidMatches[0])->first();
                    }
                }
            }

            if (!$upload) {
                Log::info('Upload not found for identifier: ' . $identifier);
                return $this->sendResponse(false, 'Media not found or already deleted');
            }

            Log::info('Found upload for identifier: ' . $identifier . ', UUID: ' . $upload->uuid . ', ID: ' . $upload->id);

            // Supprimer physiquement les fichiers associés
            $this->deletePhysicalFilesForUpload($upload);

            // Utiliser l'UUID réel pour la suppression
            $result = $this->uploadRepository->clear($upload->uuid);

            if ($result === false) {
                Log::info('Repository clear method returned false for UUID: ' . $upload->uuid);
                return $this->sendResponse(false, 'Media not found or already deleted');
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

            // Supprimer physiquement le fichier en premier
            $this->deletePhysicalFileByUrl($imageUrl);

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

                // Supprimer physiquement les fichiers associés
                $this->deletePhysicalFilesForUpload($upload);

                // Créer une requête pour passer à la méthode clear
                $clearRequest = new Request(['uuid' => $upload->uuid]);
                return $this->clear($clearRequest);
            }

            // Méthode 2: Chercher directement les Media orphelins
            $medias = Media::where('file_name', 'like', '%' . $fileName . '%')
                ->orWhere('name', 'like', '%' . $fileName . '%')
                ->get();

            $deletedCount = 0;
            foreach ($medias as $media) {
                // Supprimer physiquement les fichiers
                $this->deletePhysicalFileForMedia($media);

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
                    // Supprimer physiquement les fichiers associés
                    $this->deletePhysicalFilesForUpload($upload);

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

    private function deletePhysicalFileForMedia(Media $media)
    {
        try {
            // Chemins possibles pour les fichiers média
            $possiblePaths = [
                // Chemin principal du média
                storage_path('app/public/' . $media->id . '/' . $media->file_name),
                storage_path('app/public/' . $media->id),

                // Chemin pour les conversions
                storage_path('app/public/' . $media->id . '/conversions'),

                // Chemins alternatifs
                public_path('storage/' . $media->id . '/' . $media->file_name),
                public_path('storage/' . $media->id),
            ];

            // Ajouter aussi le chemin basé sur l'URL fournie
            $baseUrl = url('/');
            if (preg_match('/storage\/app\/public\/(\d+)\/(.+)$/', $baseUrl . '/storage/app/public/' . $media->id . '/' . $media->file_name, $matches)) {
                $id = $matches[1];
                $filename = $matches[2];
                $possiblePaths[] = storage_path('app/public/' . $id . '/' . $filename);
            }

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    if (is_dir($path)) {
                        \File::deleteDirectory($path);
                        Log::info('Physical directory deleted: ' . $path);
                    } else {
                        unlink($path);
                        Log::info('Physical file deleted: ' . $path);
                    }
                }
            }

            // Supprimer aussi le dossier parent si vide
            $parentDir = storage_path('app/public/' . $media->id);
            if (is_dir($parentDir) && $this->isDirEmpty($parentDir)) {
                rmdir($parentDir);
                Log::info('Empty parent directory deleted: ' . $parentDir);
            }

        } catch (Exception $e) {
            Log::error('Error deleting physical file for media ID ' . $media->id . ': ' . $e->getMessage());
        }
    }

    private function isDirEmpty($dir) {
        if (!is_dir($dir)) return false;

        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }

    /**
     * Delete physical file from storage
     * @param string $url
     * @return bool
     */
    private function deletePhysicalFilesForUpload(Upload $upload)
    {
        try {
            $medias = $upload->media;
            foreach ($medias as $media) {
                $this->deletePhysicalFileForMedia($media);
            }
        } catch (Exception $e) {
            Log::error('Error deleting physical files for upload: ' . $e->getMessage());
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
    /**
     * Delete physical file from storage based on URL
     * @param string $url
     * @return bool
     */
    private function deletePhysicalFileByUrl(string $url): bool
    {
        try {
            // Extraire l'ID et le nom de fichier de l'URL
            if (preg_match('/\/public\/(\d+)\/(.+)$/', $url, $matches)) {
                $id = $matches[1];
                $filename = $matches[2];

                // Chemins possibles
                $paths = [
                    storage_path('app/public/' . $id . '/' . $filename),
                    storage_path('app/public/' . $id),
                    public_path('storage/' . $id . '/' . $filename),
                    public_path('storage/' . $id),
                ];

                $deleted = false;
                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        if (is_dir($path)) {
                            \File::deleteDirectory($path);
                            Log::info('Physical directory deleted by URL: ' . $path);
                        } else {
                            unlink($path);
                            Log::info('Physical file deleted by URL: ' . $path);
                        }
                        $deleted = true;
                    }
                }

                return $deleted;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Error deleting physical file by URL: ' . $e->getMessage());
            return false;
        }
    }
}
