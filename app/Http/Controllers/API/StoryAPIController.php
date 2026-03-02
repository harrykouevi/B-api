<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Repositories\StoryRepository;
use App\Repositories\UploadRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

class StoryAPIController extends Controller
{
    /** @var StoryRepository */
    private StoryRepository $storyRepository;

    /** @var UploadRepository */
    private UploadRepository $uploadRepository;

    public function __construct(StoryRepository $storyRepo, UploadRepository $uploadRepository)
    {
        parent::__construct();
        $this->storyRepository = $storyRepo;
        $this->uploadRepository = $uploadRepository;
    }

    /**
     * Display a listing of the active Stories.
     * GET /stories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->storyRepository->pushCriteria(new RequestCriteria($request));
            $this->storyRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        // On utilise la méthode de ton Repo qui filtre les stories de moins de 24h
        $stories = $this->storyRepository->getActiveStories(auth()->id());

        return $this->sendResponse($stories, 'Stories retrieved successfully');
    }

    /**
     * Store a newly created Story in storage.
     * POST /stories
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $input = $request->all();

            // 1. Logique de sécurité / Rôles (Optionnel, comme dans ton PostController)
            $input['user_id'] = auth()->id();
            
            // 2. Gestion du média via ton UploadRepository (si tu utilises le système de cache UUID)
            // OU Gestion directe si c'est un fichier brut de la galerie
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $path = $file->store('stories', 'public');
                $input['media_path'] = asset('storage/' . $path);
            } 
            // Si tu utilises le système d'Upload par UUID (comme dans ton store de Post)
            elseif (isset($input['media_uuid'])) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['media_uuid']);
                if ($cacheUpload) {
                    $mediaItem = $cacheUpload->getMedia('image')->first() ?? $cacheUpload->getMedia('video')->first();
                    // Ici on récupère l'URL du média uploadé
                    $input['media_path'] = $mediaItem->getUrl();
                }
            }

            if (!isset($input['media_path'])) {
                return $this->sendError('Media file is required');
            }

            $input['type'] = $input['type'] ?? 'image';

            // 3. Création via le Repository
            $story = $this->storyRepository->createStory($input);

            // Optionnel : Déclencher un événement comme pour les posts
            // event(new StoryCreated($story));

            return $this->sendResponse($story->load('user'), __('lang.saved_successfully', ['operator' => 'Story']));

        } catch (Exception $e) {
            Log::error("Error storing story: " . $e->getMessage());
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified Story from storage.
     * DELETE /stories/{id}
     */
    public function destroy($id): JsonResponse
    {
        $story = $this->storyRepository->findWithoutFail($id);

        if (empty($story)) {
            return $this->sendError('Story not found');
        }

        // Vérification de sécurité : Seul l'auteur peut supprimer
        if ($story->user_id !== auth()->id()) {
            return $this->sendError('Permission denied', 403);
        }

        $this->storyRepository->delete($id);

        return $this->sendResponse($story, __('lang.deleted_successfully', ['operator' => 'Story']));
    }
}