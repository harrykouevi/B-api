<?php
/*
 * File name: CommentsReportedCriteria.php
 * Last modified: 2026.03.02 at 09:19:46
 * Author: 
 * Copyright (c) 2026
 */

namespace App\Criteria\Comments;

use App\Models\Comment;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Http\Request;


/**
 * Class CommentsReportedCriteria.
 *
 * @package namespace App\Criteria\Comments;
 */
class CommentsReportedCriteria implements CriteriaInterface
{

    /**
     * @var array|Request
     */
    private Request|array $request;

    /**
     * CommentsOfFieldsCriteria constructor.
     */
    public function __construct(Request $request )
    {
        $this->request = $request;
        
    }

    /**
     * Apply criteria in query repository
     *
     * @param string $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository): mixed
    {
            return $model->join('reports', function ($join) {
                        $join->on('reports.model_id', '=', 'comments.id')
                            ->where('reports.model_type', '=', Comment::class);
                    })
                    ->select('comments.*')
                    ->selectRaw('COUNT(reports.id) as report_count')
                    ->groupBy('comments.id')
                    ->havingRaw('COUNT(reports.id) > 0');
        
           
    }
}
