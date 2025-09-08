<?php
/*
 * File name: AddressCast.php
 * Last modified: 2024.04.18 at 17:53:30
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Casts;

use App\Models\Address as AddressModel;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Class AddressCast
 * @package App\Casts
 */
class AddressCast implements CastsAttributes
{

    /**
     * @inheritDoc
     */
    public function get($model, string $key, $value, array $attributes): AddressModel
    {
        if (!empty($value)) {
            $decodedValue = json_decode($value, true);
            $address = new AddressModel($decodedValue);
            $address->fillable[] = 'id';
            $address->id = $decodedValue['id'];
            return $address;
        }
        return new AddressModel();
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): array
    {
//        if (!$value instanceof AddressModel) {
//            throw new InvalidArgumentException('The given value is not an Address instance.');
//        }

        return ['address' => json_encode([
            'id' => $value['id'] ?? null,
            'description' => $value['description'] ?? null,
            'address' => $value['address'] ?? null,
            'latitude' => $value['latitude'] ?? null,
            'longitude' => $value['longitude'] ?? null,
        ])];
    }
}
