<?php

namespace App\Repositories;

use App\Models\OptionTemplate;
use InfyOm\Generator\Common\BaseRepository;

class OptionTemplateRepository extends BaseRepository
{

    /**
     * @inheritDoc
     */
    protected $fieldSearchable = [
        'name',
        'description',
        'price',
        'service_template_id'
    ];
    public function model(): string
    {
        return OptionTemplate::class;
    }
}