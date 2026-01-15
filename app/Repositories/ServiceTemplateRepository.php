<?php

namespace App\Repositories;

use App\Models\ServiceTemplate;
use InfyOm\Generator\Common\BaseRepository;

/**
 * ServiceTemplateRepository
 * @package App\Repositories
 * @version January 13, 2021, 11:11 am UTC
 *
 * @method Salon findWithoutFail($id, $columns = ['*'])
 * @method Salon find($id, $columns = ['*'])
 * @method Salon first($columns = ['*'])
 */
class ServiceTemplateRepository extends BaseRepository
{

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'id',
        'name',
        'description',
    ];
    
    public function model(): string
    {
        return ServiceTemplate::class;
    }
}