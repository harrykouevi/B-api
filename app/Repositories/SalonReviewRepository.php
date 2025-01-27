<?php
/*
 * File name: SalonReviewRepository.php
 * Last modified: 2024.04.18 at 17:22:49
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\SalonReview;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class SalonReviewRepository
 * @package App\Repositories
 * @version January 23, 2021, 7:42 pm UTC
 *
 * @method SalonReview findWithoutFail($id, $columns = ['*'])
 * @method SalonReview find($id, $columns = ['*'])
 * @method SalonReview first($columns = ['*'])
 */
class SalonReviewRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'review',
        'rate',
        'booking_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return SalonReview::class;
    }
}
