<?php
/*
 * File name: CustomPage.php
 * Last modified: 2024.04.18 at 17:50:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Traits\HasTranslations;
use Eloquent as Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class CustomPage
 * @package App\Models
 * @version February 24, 2021, 10:28 am CET
 *
 * @property string title
 * @property string content
 * @property boolean published
 */
class CustomPage extends Model
{

    // use HasTranslations;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'title' => 'required|max:127',
        'content' => 'required'
    ];
    public array $translatable = [
        'title',
        'content',
    ];
    public $table = 'custom_pages';
    public $fillable = [
        'title',
        'content',
        'published'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'title' => 'string',
        'content' => 'string'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',

    ];

    public function getCustomFieldsAttribute(): array
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []));
        if (!$hasCustomField) {
            return [];
        }
        $array = $this->customFieldsValues()
            ->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')
            ->where('custom_fields.in_table', '=', true)
            ->get()->toArray();

        return convertToAssoc($array, 'name');
    }

    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }


}
