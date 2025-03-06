<?php
/*
 * File name: ConversionRepository.php
 * Last modified: 2025.05.03 at 17:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2025
 */

namespace App\Repositories;

use App\Models\Conversion;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class ConversionRepository
 * @package App\Repositories
 * @version January 13, 2021, 8:02 pm UTC
 *
 * @method Conversion findWithoutFail($id, $columns = ['*'])
 * @method Conversion find($id, $columns = ['*'])
 * @method Conversion first($columns = ['*'])
 */
class ConversionRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'status',
        'affiliate_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Conversion::class;
    }
}
