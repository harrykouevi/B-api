<?php
/*
 * File name: AffiliateRepository.php
 * Last modified: 2025.02.10 at 17:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2025
 */

namespace App\Repositories;

use App\Models\Affiliate;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class AffiliateRepository
 * @package App\Repositories
 * @version January 13, 2021, 8:02 pm UTC
 *
 * @method Affiliate findWithoutFail($id, $columns = ['*'])
 * @method Affiliate find($id, $columns = ['*'])
 * @method Affiliate first($columns = ['*'])
 */
class AffiliateRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'description',
        'user_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Affiliate::class;
    }
}
