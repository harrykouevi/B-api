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
     * Créer une story et attacher les médias si nécessaire
     */
    public function createStory(array $input): Story
    {
        $input['expires_at'] = Carbon::now()->addHours(24);
        
        $model = $this->model->newInstance($input);
        $model->save();

        return $model;
    }

    /**
     * Récupérer les stories actives (le feed)
     */
    public function getActiveStories($userId)
    {
        return $this->model
            ->with(['user']) // On charge l'auteur pour l'avatar/nom sur le mobile
            // Optionnel : ->whereIn('user_id', $idsDesAmisEtMoi) 
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->get();
    }
}