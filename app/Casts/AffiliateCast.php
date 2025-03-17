<?php
/*
 * File name: AffiliateCast.php
 * Last modified: 2024.04.18 at 17:41:01
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\Affiliate as AffiliateModel;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

/**
 * Class AffiliateCast
 * @package App\Casts
 */
class AffiliateCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): AffiliateModel
    {
        if (!empty($value)) {
            $decodedValue = json_decode($value, true);
            $affiliate = new AffiliateModel($decodedValue);
            $affiliate->fillable[] = 'id';
            $affiliate->id = $decodedValue['id'];
            return $affiliate;
        }
        return new AffiliateModel();
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {

        if (!$value instanceof AffiliateModel) {
            throw new InvalidArgumentException('The given value is not an Affiliate instance.');
        }

        return ['sponsorship' => json_encode([
            'id' => $value['id'],
            'link' => $value['link'],
            'code' => $value['code'],
            'user_id' => $value['user_id'],
        ])];
    }
}
