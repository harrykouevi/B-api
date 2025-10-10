<?php

namespace App\Repositories;

use App\Models\ServiceTemplate;
use InfyOm\Generator\Common\BaseRepository;

class ServiceTemplateRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */

    protected $fieldSearchable = [
        'name',
        'description',
    ];
    public function model(): string
    {
        return ServiceTemplate::class;
    }
}