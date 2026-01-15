<?php
/*
 * File name: Coupon.php
 * Last modified: 2024.04.18 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Casts\CouponCast;
use App\Traits\HasTranslations;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * Class Coupon
 * @package App\Models
 *
 * @property integer id
 * @property string code
 * @property double discount
 * @property string discount_type
 * @property string description
 * @property DateTime expires_at
 * @property boolean enabled
 * @property Collection[] eServices
 * @property Collection[] salons
 * @property Collection[] categories
 * @property float|int $value
 */
class Coupon extends Model implements Castable
{

    // use HasTranslations;

    /**
     * Validation rules
     *
     * @var array
     */
    public static array $rules = [
        'code' => 'required|unique:coupons|max:50',
        'discount' => 'required|numeric|min:0',
        'discount_type' => 'required',
        'expires_at' => 'required|date|after_or_equal:tomorrow'
    ];
    public array $translatable = [
        'description',
    ];
    public $table = 'coupons';
    public $fillable = [
        'code',
        'discount',
        'discount_type',
        'description',
        'expires_at',
        'enabled'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'string',
        'discount' => 'double',
        'value' => 'double',
        'discount_type' => 'string',
        'description' => 'string',
        'expires_at' => 'datetime',
        'enabled' => 'boolean'
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',

    ];

    /**
     * @param array $arguments
     * @return string
     */
    public static function castUsing(array $arguments): string
    {
        return CouponCast::class;
    }

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

    public function discountables(): HasMany
    {
        return $this->hasMany(Discountable::class, 'coupon_id');
    }

    public function eServices(): MorphToMany
    {
        return $this->morphedByMany(EService::class, 'discountable');
    }

    public function categories(): MorphToMany
    {
        return $this->morphedByMany(Category::class, 'discountable');
    }

    public function app_charges(): MorphToMany
    {
        $relation = $this->morphedByMany(Wallet::class, 'discountable');
        $relation->where('wallets.id', setting('app_default_wallet_id'));

        return $relation;
    }

    public function salons(): MorphToMany
    {
        return $this->morphedByMany(Salon::class, 'discountable');
    }

    public function getValue($eServices , $options = Null ): Coupon
    {
       $serviceprices = 0 ;
        $couponValue = 0;
        $app_w = $this->app_charges()->first() ;
        if( is_null($app_w)){
            $eServicesOfCategories = $this->categories->pluck('eServices')->flatten()->toArray();
            $eServicesOfSalons = $this->salons->pluck('eServices')->flatten()->toArray();
            $couponEServices = $this->eServices->concat($eServicesOfCategories)->concat($eServicesOfSalons);
            $couponEServicesIds = $couponEServices->pluck('id')->toArray();
        }else{
            $couponEServicesIds = Eservice::pluck('id')->toArray();
        }

        foreach ($eServices as $eService) {
            $serviceprices += $eService->getPrice() ;
            if (in_array($eService->id, $couponEServicesIds)) {
                if ($this->discount_type == 'percent') {
                    $couponValue += $eService->getPrice() * $this->discount / 100;
                } else {
                    $couponValue += $this->discount;
                }
                
                if(!is_null($options)){
                    $serviceOptions = $options->where('e_service_id', $eService->id);
                    foreach ($serviceOptions as $option) {
                        $serviceprices += $option->price ;
                        if ($this->discount_type == 'percent') {
                            $couponValue += $option->price * $this->discount / 100;
                        } else {
                        // $couponValue += $this->discount;
                        }
                    }
                }
            }
        }

        
       
        $this->value = $couponValue;
        unset($this['eServices'], $this['salons'], $this['categories']);
        return $this;
    }

}
