<?php
/*
 * File name: CustomField.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomField
 * @package App\Models
 * @version July 24, 2018, 9:13 pm UTC
 *
 * @property string name
 * @property string type
 * @property boolean disabled
 * @property boolean required
 * @property boolean in_table
 * @property int bootstrap_column
 * @property int order
 * @property string custom_field_model
 * @method hasMedia(mixed $collection)
 * @method getFirstMedia(mixed $collection)
 */
class CustomField extends Model
{

    public $table = 'custom_fields';



    public $fillable = [
        'name',
        'type',
        'values',
        'disabled',
        'required',
        'in_table',
        'bootstrap_column',
        'order',
        'custom_field_model'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'type' => 'string',
        'values' => 'array',
        'disabled' => 'boolean',
        'required' => 'boolean',
        'in_table' => 'boolean',
        'custom_field_model' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'name' => 'required',
        'type' => 'required',
        'bootstrap_column' => 'min:1|max:12',
        'custom_field_model' => 'required'
    ];

    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [

    ];


}
