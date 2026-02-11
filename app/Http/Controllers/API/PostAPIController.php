<?php

namespace App\Http\Controllers\API;
use App\Models\Comment;
use App\Http\Controllers\API\DB;
use App\Criteria\Posts\PostsOfUserCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Models\Media;
use App\Repositories\PostRepository;
use App\Repositories\PostTargetRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Illuminate\Validation\ValidationException;
use App\Repositories\UploadRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Like;
use App\Models\Post;



class PostAPIController extends Controller
{
    /** @var PostTargetRepository */
    private PostTargetRepository $postTargetRepository ;

    /** @var PostRepository */
    private PostRepository $postRepository;

    /** @var UploadRepository */
    private UploadRepository $uploadRepository;

    public function __construct(PostRepository $postRepo, UploadRepository $uploadRepository , PostTargetRepository  $postTargetRepository )
    {
        $this->uploadRepository = $uploadRepository;
        $this->postTargetRepository = $postTargetRepository ;
        $this->postRepository = $postRepo;
        parent::__construct();
    }

    /**
     * Display a listing of the Posts with pagination.
     * GET /posts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->postRepository->pushCriteria(new RequestCriteria($request));
            $this->postRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->postRepository->withTargets();
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $posts = $this->postRepository->all();

       

        return $this->sendResponse($posts, 'Posts retrieved successfully');
    }

    /**
     * Store a newly created EService in storage.
     *
     * @param CreatePostRequest $request
     *
     * @return JsonResponse
     */
     
    public function store(CreatePostRequest $request): JsonResponse
   {   
    {
        try {
            $input = $request->all();
            if (auth()->user()->hasAnyRole(['salon owner'])) {
                $input['users'] = [auth()->id()];
                $input['published_at'] = now();
                $input['visibility'] = 'public';
                $input['status'] = 'published';
                $input['uuid'] = (
                        isset($input['uuid']) &&
                        Str::isUuid($input['uuid'])
                    )? $input['uuid'] : (string) Str::uuid();
                    
                if (isset($input['vimeo_id'])) {
                $input['vimeo_id'] = preg_replace('/[^0-9]/', '', $input['vimeo_id']);
                    }
                
                $request->loadMedia('image');
                 $post = $this->postRepository->create($input);
            
                $m = clone($post);

                if (isset($input['e_service_id']) && $input['e_service_id'] ) {
                   
                    $data =[] ; 
                    $data['post_id'] = $m->id;
                    $data['model_type'] = 'App\Models\EService' ;
                    $data['model_id'] = $input['e_service_id'];
                    $cacheUpload = $this->postTargetRepository->create($data);
                        
                }

                if (isset($input['target']) && $input['target'] && is_array($input['target'])) {
                    foreach ($input['target'] as $target) {
                        $data =[] ; 
                        $data['post_id'] = $m->id;
                        $data['model_type'] = 'App\Models\EService' ;
                        $data['model_id'] = $input['e_service'];
                        //$cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                        
                    }
                }
                $post->targetModels();
                Log::info([$post->toArray()]) ;
            }

            // La création via repository inclura automatiquement vimeo_id si tu l'as ajouté au $fillable du modèle
           
             if (isset($input['image']) && $input['image'] && is_array($input['image'])) {
                foreach ($input['image'] as $fileUuid) {
                    $cacheUpload = $this->uploadRepository->getByUuid($fileUuid);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($m, 'image');
                }
            }
            
            $post->loadMedia('image');
            Log::info([$post->toArray()]);
                    
                    }

      
       catch (ValidationException $e) {
        return $this->sendError(array_values($e->errors()), 422);
    } 
    catch (Exception $e) {
        return $this->sendError($e->getMessage(), 500);
    }
    
    return $this->sendResponse($post, __('lang.saved_successfully', ['operator' => __('lang.post')]));
} 
 }

      /**
     * Display the specified Post.
     * GET|HEAD /posts/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show( $id, Request $request): JsonResponse
    {
        { }
        try {
            $this->postRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->postRepository->pushCriteria(new RequestCriteria($request));
            $this->postRepository->withTargets();

        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        if(is_numeric($id)){ 
            $post = $this->postRepository->findWithoutFail($id);
        }else{
            if(Str::isUuid($id)){ 
                $post = $this->postRepository->findByField('uuid', $id)->first();
            }else{
                return $this->sendError('Post not found');
            }
        }
       
        return $this->sendResponse($post, 'Post retrieved successfully');
    }

     /**
     * Remove the specified EService from storage.
     *
     * @param int|string $id
     *
     * @return JsonResponse
     * @throws RepositoryException
     */
       public function destroy( $id): JsonResponse
    {
       
    $this->postRepository->pushCriteria(new PostsOfUserCriteria(auth()->id()));

        if(is_numeric($id)){ 
            $post = $this->postRepository->findWithoutFail($id);
        }else{
            if(Str::isUuid($id)){ 
                $post = $this->postRepository->getByUuid($id) ;
            }else{
                return $this->sendError('Post not found');
            }
        }
        
        if (empty($post)) {
            return $this->sendError('Post not found');
        }
        $this->postRepository->delete($id);
        return $this->sendResponse($post, __('lang.deleted_successfully', ['operator' => __('lang.post')]));

    }
    /**
 * POST /api/posts/{id}/like
 */
/**
 * POST /api/posts/{id}/like
 */
public function like($id): JsonResponse
{
    if (is_numeric($id)) {
        $post = $this->postRepository->findWithoutFail($id);
    } else {
        $post = Str::isUuid($id) ? $this->postRepository->findByField('uuid', $id)->first() : null;
    }

    if (empty($post)) {
        return $this->sendError('Post not found');
    }

    $like = Like::where('user_id', auth()->id())->where('post_id', $post->id)->first();

    if (!$like) {
        Like::create([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
        ]);

        // FORÇAGE : On cible l'ID numérique (1) explicitement
        \DB::table('posts')->where('id', $post->id)->increment('like_count');
    }

    return $this->sendResponse($post->fresh(), 'Post liked successfully');
}

/**
 * DELETE /api/posts/{id}/like
 */
public function unlike($id): JsonResponse
{
    if (is_numeric($id)) {
        $post = $this->postRepository->findWithoutFail($id);
    } else {
        $post = Str::isUuid($id) ? $this->postRepository->findByField('uuid', $id)->first() : null;
    }

    if (empty($post)) {
        return $this->sendError('Post not found');
    }

    $deleted = Like::where('user_id', auth()->id())
        ->where('post_id', $post->id)
        ->delete();

    if ($deleted) {
        // FORÇAGE : On décrémente sur l'ID numérique
        DB::table('posts')->where('id', $post->id)->decrement('like_count');
    }

    return $this->sendResponse($post->fresh(), 'Post unliked successfully');
}
/**
 * POST /api/posts/{id}/comments
 */
public function storeComment(Request $request, $id): JsonResponse
{
    // 1. Validation du contenu (Étape "Contenu" de ton flux)
    $request->validate([
        'content' => 'required|string|min:1',
    ]);

    // 2. Recherche du post (Logique UUID/ID)
    if (is_numeric($id)) {
        $post = $this->postRepository->findWithoutFail($id);
    } else {
        $post = Str::isUuid($id) ? $this->postRepository->findByField('uuid', $id)->first() : null;
    }

    if (empty($post)) {
        return $this->sendError('Post not found');
    }

    // 3. Stockage (Étape "Auteur, Contenu, Date")
    // Laravel remplit 'created_at' (la date) automatiquement
    $comment = Comment::create([
        'content' => $request->input('content'),
        'user_id' => auth()->id(), // L'auteur connecté
        'post_id' => $post->id,    // Le lien vers le post (ID: 1 par ex)
    ]);

    // 4. Mise à jour du compteur physique
    \DB::table('posts')->where('id', $post->id)->increment('comment_count');

    // On retourne le commentaire avec les infos de l'auteur pour l'affichage mobile
    return $this->sendResponse($comment->load('user'), 'Commentaire ajouté avec succès');
}
}