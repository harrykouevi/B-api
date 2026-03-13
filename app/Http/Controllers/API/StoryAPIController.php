<?php

namespace App\Http\Controllers\API;

use App\Events\AttachModelToVideoUploadEvent;
use App\Events\MyStoryCreatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStoryRequest;
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
        $stories = $this->storyRepository->getActiveStories();

        return $this->sendResponse($stories, 'Stories retrieved successfully');
    }

    /**
     * Store a newly created Story in storage.
     * POST /stories
     */
    public function store(CreateStoryRequest $request): JsonResponse
    {
        try {
            $input = $request->all();

            // 1. Logique de sécurité / Rôles (Optionnel, comme dans ton PostController)
            $input['user_id'] = auth()->id();
            $input['uuid'] = (isset($input['uuid']) && Str::isUuid($input['uuid'])) ? $input['uuid'] : (string) Str::uuid();
            $story = $this->storyRepository->create($input);

            if (isset($input['media']) && is_array($input['media'])) {
                
                // Si tu utilises le système d'Upload par UUID (comme dans ton store de Post)
                foreach ($input['media'] as $fileUuid) {
                    // liaison de l'image uploadé (recup de l'uuid de l'image) avec le post
                    if(Str::isUuid($fileUuid)){ 
                        event(new AttachModelToVideoUploadEvent($fileUuid, $story));
                    }
                }

                // 2. Gestion du média via ton UploadRepository (si tu utilises le système de cache UUID)
                // OU Gestion directe si c'est un fichier brut de la galerie
                if ($request->hasFile('media')) {
                    foreach($request->file('media') as $file){
                        if (!$file->isValid()) {
                            continue;
                        }
                        $in = [
                            'uuid' =>  (string) Str::uuid() ,
                            'field' => 'cloudmedia' ,
                        ] ;
                        $this->uploadRepository->createWithMedia($file,$in,$story);
                    }
                } 
                
            }
            $story->load('media');
            Log::info([$story->toArray()]);

        } catch (Exception $e) {
            Log::error("Error storing story: " . $e->getMessage());
            return $this->sendError($e->getMessage(), 500);
        }

        if (isset($input['media']) && is_array($input['media'])) {
            $message = "Votre story a bien été enregistré, mais il est en cours de traitement car il contient un média.";
        } else {
            $message = "Votre story a bien été enregistré.";
        }
        
        event(new MyStoryCreatedEvent($story,$message));

        return $this->sendResponse($story, __('lang.saved_successfully', ['operator' => 'Story']));
        // event(new StoryCreated($story));
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