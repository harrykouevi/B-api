<?php
/*
 * File name: UploadRepository.php
 * Last modified: 2024.04.18 at 17:53:47
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Media;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class UploadRepository
 * @package App\Repositories
 * @version June 12, 2018, 11:30 am UTC
 *
 * @method Upload findWithoutFail($id, $columns = ['*'])
 * @method Upload find($id, $columns = ['*'])
 * @method Upload first($columns = ['*'])
 */
class UploadRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [

    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Upload::class;
    }

    /**
     * @param $uuid
     * @return bool|null
     */
    public function clear($uuid): ?bool
    {
        $uploadModel = $this->getByUuid($uuid);

        if ($uploadModel) {
            $uploadModel->media()->delete();
            return $uploadModel->delete();
        }

        // Retourner false si l'upload n'existe pas
        return false;
    }

    /**
     * @param $uuids
     * @return bool|null
     */
    public function clearWhereIn($uuids): ?bool
    {
        return Upload::query()->whereIn('uuid', $uuids)->delete();
    }

    public function getByUuid($uuid = ''): null|Model
    {
        if (empty($uuid)) {
            return null;
        }

        return Upload::query()->where('uuid', $uuid)->first();
    }


    /**
     * clear all uploaded cache
     */
    public function clearAll(): void
    {
        Upload::query()->where('id', '>', 0)->delete();
        Media::query()->where('model_type', '=', 'App\Models\Upload')->delete();
    }

    /**
     * @return Builder[]|Collection
     */
    public function allMedia($collection = null): Collection|array
    {
        $medias = Media::query()->where('model_type', '=', 'App\Models\Upload');
        if ($collection) {
            $medias = $medias->where('collection_name', $collection);
        }
        $medias = $medias->orderBy('id', 'desc')->get();
        return $medias;
    }


    public function collectionsNames()
    {
        $medias = Media::all('collection_name')->pluck('collection_name', 'collection_name')->map(function ($c) {
            return ['value' => $c,
                'title' => Str::title(preg_replace('/_/', ' ', $c))
            ];
        })->unique();
        unset($medias['default']);
        $medias->prepend(['value' => 'default', 'title' => 'Default'], 'default');
        return $medias;
    }

}
