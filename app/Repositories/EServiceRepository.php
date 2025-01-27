<?php
/*
 * File name: EServiceRepository.php
 * Last modified: 2024.04.18 at 17:21:53
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\EService;
use InfyOm\Generator\Common\BaseRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use Prettus\Repository\Traits\CacheableRepository;

/**
 * Class EServiceRepository
 * @package App\Repositories
 * @version January 19, 2021, 1:59 pm UTC
 *
 * @method EService findWithoutFail($id, $columns = ['*'])
 * @method EService find($id, $columns = ['*'])
 * @method EService first($columns = ['*'])
 */
class EServiceRepository extends BaseRepository implements CacheableInterface
{

    use CacheableRepository;

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'price',
        'discount_price',
        'duration',
        'description',
        'featured',
        'available',
        'enable_booking',
        'enable_at_salon',
        'enable_at_customer_address',
        'salon_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return EService::class;
    }

    /**
     * @return array
     */
    public function groupedBySalons(): array
    {
        $eServices = [];
        foreach ($this->all() as $model) {
            if (!empty($model->salon)) {
                $eServices[$model->salon->name][$model->id] = $model->name;
            }
        }
        return $eServices;
    }
}
