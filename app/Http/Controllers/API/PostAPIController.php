<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\PostRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

class PostAPIController extends Controller
{
    /** @varPostRepository */
    private PostRepository $postRepository;

    public function __construct(PostRepository $postRepo)
    {
       
        
        
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
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $posts = $this->postRepository->all();

       

        return $this->sendResponse($posts, 'Posts retrieved successfully');
    }
}