<?php
/*
 * File name: SalonCast.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\Salon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Class SalonCast
 * @package App\Casts
 */
class SalonCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): ?Salon
    {
        if (empty($value)) {
            return null; // Gérer les cas où le JSON est vide ou null
        }

        $decodedValue = json_decode($value, true);
        $salon = Salon::find($decodedValue['id']);
        // salon exist in database
        if (!empty($salon)) {
            return $salon;
        }

       

        $data = json_decode($value, true);
        // if not exist the clone will loaded
        // create new salon based on values stored on database
        $salon = new Salon($decodedValue);
        // push id attribute fillable array
        $salon->fillable[] = 'id';
        // assign the id to salon object
        $salon->id = $decodedValue['id'];
        return $salon;
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {
//        if (!$value instanceof \Eloquent) {
//            throw new InvalidArgumentException('The given value is not an Salon instance.');
//        }
        return [
            'salon' => json_encode([
                'id' => $value['id'],
                'name' => $value['name'],
                'phone_number' => $value['phone_number'],
                'mobile_number' => $value['mobile_number'],
            ])
        ];
    }
}
