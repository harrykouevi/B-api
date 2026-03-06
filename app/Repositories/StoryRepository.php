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

   
    /**
     * Récupérer les stories actives (le feed)
     */
    public function getActiveStories()
    {
        return $this->model
            // Optionnel : ->whereIn('user_id', $idsDesAmisEtMoi) 
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->get();
    }
}