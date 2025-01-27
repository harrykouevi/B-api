<?php
/*
 * File name: TestHelper.php
 * Last modified: 2024.04.11 at 12:07:11
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Tests\Helpers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * get admin user role
 * @return User|Model
 */
class TestHelper
{
    public static function getAdmin()
    {
        return User::firstWhere('email', 'admin@demo.com');
    }

    /**
     * Get provider user role
     * @return User|Model
     */
    public static function getProvider(): Model|User
    {
        return User::firstWhere('email', 'provider@demo.com');
    }

    /**
     * Get customer user
     * @return User|Model
     */
    public static function getCustomer(): Model|User
    {
        return User::firstWhere('email', 'customer@demo.com');
    }

    /**
     * Get random user
     * @return User|Model
     */
    public static function getRandomUser(): Model|User
    {

        return User::firstWhere('email', 'customer@demo.com');
    }

    /**
     * @param int $length
     * @param array $data
     * @return array
     */
    public static function generateJsonArray(int $length, array $data): array
    {
        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = $data;
        }
        return $result;
    }

    /*
    getTranslatableColumn method here that will return the translatable attribute like "{\"en\":\"$attribute\"}", where $attribute is the value of the translatable attribute
    */

    public static function getTranslatableColumn($attribute): string
    {
        return "{\"en\":\"$attribute\"}";
    }
}

