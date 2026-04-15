<?php
/*
 * File name: CouponUse.php
 * Last modified: 2026.04.15 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Models;

use App\Casts\CouponUseCast;
use App\Traits\HasTranslations;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * Class CouponUse
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
class CouponUse extends Model 
{

    // use HasTranslations;

    public $table = 'coupon_uses';
    public $fillable = [
        'coupon_id',
        'user_id',
    ];
    
    
  
    
}
