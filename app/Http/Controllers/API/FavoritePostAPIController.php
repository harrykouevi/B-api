<?php
/*
 * File name: FavoritePostAPIController.php
 * Last modified: 2026.02.12 at 16:22:27
 * Author: Harry.Kouevi
 * Copyright (c) 2026
 */

namespace App\Http\Controllers\API;


use App\Criteria\FavoritePosts\DistinctCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFavoritePostRequest;
use App\Http\Requests\UpdateFavoritePostRequest;
use App\Models\Favorite;
use App\Repositories\FavoritePostRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Nwidart\Modules\Process\Updater;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\Auth;


/**
 * Class FavoritePostAPIController
 * @package App\Http\Controllers\API
 */
class FavoritePostAPIController extends Controller
{
    /** @var  FavoritePostRepository */
    private FavoritePostRepository $favoritePostRepository;

    public function __construct(FavoritePostRepository $favoriteRepo)
    {
        parent::__construct();
        $this->favoritePostRepository = $favoriteRepo;
    }


    /**
     * Store a newly created Favorite in storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(CreateFavoritePostRequest $request): JsonResponse
    {
        $input = $request->all();
        try {
           $favorite = $this->favoritePostRepository->updateOrCreate($input);
        } catch (ValidatorException) {
            return $this->sendError('Favorite not found');
        }

        return $this->sendResponse($favorite->toArray(), __('lang.saved_successfully', ['operator' => __('lang.favorite')]));
    }

   
    /**
     * Remove the specified Favorite from storage.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request):JsonResponse
    {
        $input = $request->only('user_id', 'post_id');
        $input['user_id'] = Auth::id();
        try {
            $favorite = $this->favoritePostRepository->deleteWhere($input) > 0;
        } catch (Exception) {
            return $this->sendError('Favorite not found');
        }
        return $this->sendResponse($favorite, __('lang.deleted_successfully', ['operator' => __('lang.favorite')]));

    }
}
