<?php

namespace App\Repositories;

use App\Models\Story;
use InfyOm\Generator\Common\BaseRepository;
use Carbon\Carbon;

class StoryRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'user_id',
        'type'
    ];

    public function model(): string
    {
        return Story::class;
    }

   
   
}