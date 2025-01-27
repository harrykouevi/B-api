<?php
/*
 * File name: FaqRepository.php
 * Last modified: 2024.04.18 at 17:21:54
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Repositories;

use App\Models\Faq;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class FaqRepository
 * @package App\Repositories
 * @version August 29, 2019, 9:39 pm UTC
 *
 * @method Faq findWithoutFail($id, $columns = ['*'])
 * @method Faq find($id, $columns = ['*'])
 * @method Faq first($columns = ['*'])
 */
class FaqRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'question',
        'answer',
        'faq_category_id'
    ];

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Faq::class;
    }
}
