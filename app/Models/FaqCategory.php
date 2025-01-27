<?php
/*
 * File name: FaqCategory.php
 * Last modified: 2024.04.18 at 17:50:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Traits\HasTranslations;
use Eloquent as Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class FaqCategory
 * @package App\Models
 * @version August 29, 2019, 9:38 pm UTC
 *
 * @property Collection Faq
 * @property string name
 */
class FaqCategory extends Model
{

    use HasTranslations;
    use HasFactory;

    public array $translatable = [
        'name',
    ];
    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'name' => 'required|max:127'
    ];
    public $table = 'faq_categories';
    public $fillable = [
        'name'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',

    ];

    protected $hidden = [
        "created_at",
        "updated_at",
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

    /**
     * @return HasMany
     **/
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'faq_category_id');
    }

}
