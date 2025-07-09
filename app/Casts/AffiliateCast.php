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
         // Si valeur null => return null
        if (is_null($value)) {
            return new AffiliateModel();
        }
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

        // Si on fournit un tableau au lieu d'un Affiliate, on convertit
        if (is_array($value)) {
            $value = new AffiliateModel($value);
        }

        if (!$value instanceof AffiliateModel) {
            throw new InvalidArgumentException('The given value is not an Affiliate instance.');
        }

        // return ['sponsorship' => json_encode([
        //     'id' => $value['id'],
        //     'link' => $value['link'],
        //     'code' => $value['code'],
        //     'user_id' => $value['user_id'],
        // ])];
         // Sauvegarde sous forme de JSON
        return $value->toArray(); // Assure-toi que Affiliate a une méthode toArray()
    
    }
}
