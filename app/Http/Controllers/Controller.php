<?php
/*
 * File name: Controller.php
 * Last modified: 2024.04.18 at 17:22:51
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use InfyOm\Generator\Utils\ResponseUtil;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
    }

    /**
     * @param $result
     * @param $message
     * @return JsonResponse
     */
    public function sendResponse($result, $message): JsonResponse
    {
        return Response::json(ResponseUtil::makeResponse($message, $result));
    }

    /**
     * @param $error
     * @param int $code
     * @return JsonResponse
     */
    public function sendError($error, int $code = 200): JsonResponse
    {
        return Response::json(ResponseUtil::makeError($error), $code);
    }

     /**
     * @param $error
     * @param int $code
     * @return JsonResponse
     */
    public function send($error, int $code = 200): JsonResponse
    {
        return Response::json($error, $code);
    }


    /**
     * Example:
     * only=name;rate;total_reviews;categories.has_media;categories.media;categories.media.url
     * @param Request $request
     * @param Collection $collection
     */
    protected function filterCollection(Request $request, Collection $collection): void
    {
        $only = $this->getOnlyArray($request);
        $collection->each->setVisible(array_keys($only));
        $collection->map(function ($item) use ($only) {
            foreach ($only as $key => $filter) {
                try {
                    if (is_array($filter) && count($filter) > 0) {
                        foreach ($filter as $key1 => $filter1) {
                            if (is_array($filter1) && count($filter1) > 0) {
                                $item->getRelations()[$key]->map(function ($item) use ($key1, $filter1) {
                                    $item->getRelations()[$key1]->each->setVisible(array_keys($filter1));
                                });
                            }
                        }
                        if (isset($item->getRelations()[$key]) && $item->getRelations()[$key] instanceof Collection) {
                            $item->getRelations()[$key]->each->setVisible(array_keys($filter));
                        } else if (isset($item->getRelations()[$key])) {
                            $item->getRelations()[$key]->setVisible(array_keys($filter));
                        } else if ($item->$key instanceof Model) {
                            $item->$key->setVisible(array_keys($filter));
                        }
                    }
                } catch (Exception) {
                    dd($key);
                }
            }
            return $item;
        });
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getOnlyArray(Request $request): array
    {
        $only = [];
        if ($request->has('only')) {
            $only = $request->get('only');
            $only = explode(';', $only);
            $only = $this->buildOnlyTree($only, '.');
        }
        return $only;
    }

    protected function buildOnlyTree($categoryLines, $separator): array
    {
        $catTree = array();
        foreach ($categoryLines as $catLine) {
            $path = explode($separator, $catLine);
            $node = &$catTree;
            foreach ($path as $cat) {
                $cat = trim($cat);
                if (!isset($node[$cat])) {
                    $node[$cat] = array();
                }
                $node = &$node[$cat];
            }
        }
        return $catTree;
    }

    /**
     * Example:
     * only=name;rate;total_reviews;categories.has_media;categories.media;categories.media.url
     * @param Request $request
     * @param Model $model
     */
    protected function filterModel(Request $request, Model $model): void
    {
        $only = $this->getOnlyArray($request);
        $model->setVisible(array_keys($only));
        foreach ($only as $key => $filter) {
            if (is_array($filter) && count($filter) > 0) {
                foreach ($filter as $key1 => $filter1) {
                    if (is_array($filter1) && count($filter1) > 0) {
                        $model->getRelations()[$key]->map(function ($item) use ($key1, $filter1) {
                            $item->getRelations()[$key1]->each->setVisible(array_keys($filter1));
                        });
                    }
                }
                $model->getRelations()[$key]->each->setVisible(array_keys($filter));
            }
        }
    }

    protected function limitOffset(Request $request, Collection &$collection): void
    {
        $limit = $request->get('limit');
        $offset = $request->get('offset');

        if ($offset && $limit) {
            $collection = $collection->skip($offset);
        }
        if ($limit) {
            $collection = $collection->take($limit);
        }

    }
}
