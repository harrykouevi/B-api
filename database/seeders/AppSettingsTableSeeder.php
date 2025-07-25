<?php
/*
 * File name: AppSettingsTableSeeder.php
 * Last modified: 2024.04.09 at 08:00:26
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class AppSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {


        // DB::table('app_settings')->truncate();


        DB::table('app_settings')->insert(array(
            array(
                'id' => 7,
                'key' => 'date_format',
                'value' => 'l jS F Y (H:i:s)',
            ),
            array(
                'id' => 8,
                'key' => 'language',
                'value' => 'fr',//modified
            ),
            array(
                'id' => 17,
                'key' => 'is_human_date_format',
                'value' => '1',
            ),
            array(
                'id' => 18,
                'key' => 'app_name',
                'value' => 'Barber Shop',//modified
            ),
            array(
                'id' => 19,
                'key' => 'app_short_description',
                'value' => 'Manage Mobile Application',
            ),
            array(
                'id' => 20,
                'key' => 'mail_driver',
                'value' => 'smtp',
            ),
            array(
                'id' => 21,
                'key' => 'mail_host',
                'value' => 'smtp.hostinger.com',
            ),
            array(
                'id' => 22,
                'key' => 'mail_port',
                'value' => '587',
            ),
            array(
                'id' => 23,
                'key' => 'mail_username',
                'value' => 'test@demo.com',
            ),
            array(
                'id' => 24,
                'key' => 'mail_password',
                'value' => '-',
            ),
            array(
                'id' => 25,
                'key' => 'mail_encryption',
                'value' => 'ssl',
            ),
            array(
                'id' => 26,
                'key' => 'mail_from_address',
                'value' => 'test@demo.com',
            ),
            array(
                'id' => 27,
                'key' => 'mail_from_name',
                'value' => 'BHC',
            ),
            array(
                'id' => 30,
                'key' => 'timezone',
                'value' => 'Africa/Lome',
            ),
            array(
                'id' => 32,
                'key' => 'theme_contrast',
                'value' => 'light',
            ),
            array(
                'id' => 33,
                'key' => 'theme_color',
                'value' => 'olive',
            ),
            array(
                'id' => 34,
                'key' => 'app_logo',
                'value' => '020a2dd4-4277-425a-b450-426663f52633',
            ),
            array(
                'id' => 35,
                'key' => 'nav_color',
                'value' => 'navbar-dark navbar-olive',
            ),
            array(
                'id' => 38,
                'key' => 'logo_bg_color',
                'value' => 'text-light  navbar-olive',
            ),
            array(
                'id' => 68,
                'key' => 'facebook_app_id',
                'value' => '518416208939727',
            ),
            array(
                'id' => 69,
                'key' => 'facebook_app_secret',
                'value' => '93649810f78fa9ca0d48972fee2a75cd',
            ),
            array(
                'id' => 71,
                'key' => 'twitter_app_id',
                'value' => 'twitter',
            ),
            array(
                'id' => 72,
                'key' => 'twitter_app_secret',
                'value' => 'twitter 1',
            ),
            array(
                'id' => 74,
                'key' => 'google_app_id',
                'value' => '527129559488-roolg8aq110p8r1q952fqa9tm06gbloe.apps.googleusercontent.com',
            ),
            array(
                'id' => 75,
                'key' => 'google_app_secret',
                'value' => 'FpIi8SLgc69ZWodk-xHaOrxn',
            ),
            array(
                'id' => 77,
                'key' => 'enable_google',
                'value' => '1',
            ),
            array(
                'id' => 78,
                'key' => 'enable_facebook',
                'value' => '1',
            ),
            array(
                'id' => 93,
                'key' => 'enable_stripe',
                'value' => '1',
            ),
            array(
                'id' => 94,
                'key' => 'stripe_key',
                'value' => 'pk_test_pltzOnX3zsUZMoTTTVUL4O41',
            ),
            array(
                'id' => 95,
                'key' => 'stripe_secret',
                'value' => 'sk_test_o98VZx3RKDUytaokX4My3a20',
            ),
            array(
                'id' => 101,
                'key' => 'custom_field_models.0',
                'value' => 'App\\Models\\User',
            ),
            array(
                'id' => 104,
                'key' => 'default_tax',
                'value' => '10',
            ),
            array(
                'id' => 107,
                'key' => 'default_currency',
                'value' => 'XOF',
            ),
            array(
                'id' => 108,
                'key' => 'fixed_header',
                'value' => '1',
            ),
            array(
                'id' => 109,
                'key' => 'fixed_footer',
                'value' => '0',//modified
            ),
            array(
                'id' => 110,
                'key' => 'fcm_key',
                'value' => '',//modified
            ),
            array(
                'id' => 111,
                'key' => 'enable_notifications',
                'value' => '1',
            ),
            array(
                'id' => 112,
                'key' => 'paypal_username',
                'value' => 'sb-z3gdq482047_api1.business.example.com',
            ),
            array(
                'id' => 113,
                'key' => 'paypal_password',
                'value' => '-',
            ),
            array(
                'id' => 114,
                'key' => 'paypal_secret',
                'value' => '-',
            ),
            array(
                'id' => 115,
                'key' => 'enable_paypal',
                'value' => '1',
            ),
            array(
                'id' => 116,
                'key' => 'main_color',
                'value' => '#09594B',
            ),
            array(
                'id' => 117,
                'key' => 'main_dark_color',
                'value' => '#ADC148',
            ),
            array(
                'id' => 118,
                'key' => 'second_color',
                'value' => '#042819',
            ),
            array(
                'id' => 119,
                'key' => 'second_dark_color',
                'value' => '#CCDDCF',
            ),
            array(
                'id' => 120,
                'key' => 'accent_color',
                'value' => '#BBC4C1',
            ),
            array(
                'id' => 121,
                'key' => 'accent_dark_color',
                'value' => '#99AA99',
            ),
            array(
                'id' => 122,
                'key' => 'scaffold_dark_color',
                'value' => '#2C2C2C',
            ),
            array(
                'id' => 123,
                'key' => 'scaffold_color',
                'value' => '#FAFAFA',
            ),
            array(
                'id' => 124,
                'key' => 'google_maps_key',
                'value' => 'AIzaSyDiMsH2JW4MQkdPaJFilh1lGAvyRq_AwSE',//modified
            ),
            array(
                'id' => 125,
                'key' => 'mobile_language',
                'value' => 'fr',//modified
            ),
            array(
                'id' => 126,
                'key' => 'app_version',
                'value' => '1.0.0',
            ),
            array(
                'id' => 127,
                'key' => 'enable_version',
                'value' => '1',
            ),
            array(
                'id' => 128,
                'key' => 'default_currency_id',
                'value' => '1',
            ),
            array(
                'id' => 129,
                'key' => 'default_currency_code',
                'value' => 'XOF',
            ),
            array(
                'id' => 130,
                'key' => 'default_currency_decimal_digits',
                'value' => '2',
            ),
            array(
                'id' => 131,
                'key' => 'default_currency_rounding',
                'value' => '0',
            ),
            array(
                'id' => 132,
                'key' => 'currency_right',
                'value' => 'XOF',
            ),
            array(
                'id' => 133,
                'key' => 'distance_unit',
                'value' => 'km',
            ),
            array(
                'id' => 134,
                'key' => 'default_theme',
                'value' => 'light',
            ),
            array(
                'id' => 135,
                'key' => 'enable_paystack',
                'value' => '1',
            ),
            array(
                'id' => 136,
                'key' => 'paystack_key',
                'value' => 'pk_test_d754715fa3fa9048c9ab2832c440fb183d7c91f5',
            ),
            array(
                'id' => 137,
                'key' => 'paystack_secret',
                'value' => 'sk_test_66f87edaac94f8adcb28fdf7452f12ccc63d068d',
            ), array(
                'id' => 138,
                'key' => 'enable_flutterwave',
                'value' => '1',
            ),
            array(
                'id' => 139,
                'key' => 'flutterwave_key',
                'value' => 'FLWPUBK_TEST-d465ba7e4f6b86325cb9881835726402-X',
            ),
            array(
                'id' => 140,
                'key' => 'flutterwave_secret',
                'value' => 'FLWSECK_TEST-d3f8801da31fc093fb1207ea34e68fbb-X',
            ),
            array(
                'id' => 141,
                'key' => 'enable_stripe_fpx',
                'value' => '1',
            ),
            array(
                'id' => 142,
                'key' => 'stripe_fpx_key',
                'value' => 'pk_test_51IQ0zvB0wbAJesyPLo3x4LRgOjM65IkoO5hZLHOMsnO2RaF0NlH7HNOfpCkjuLSohvdAp30U5P1wKeH98KnwXkOD00mMDavaFX',
            ),
            array(
                'id' => 143,
                'key' => 'stripe_fpx_secret',
                'value' => 'sk_test_51IQ0zvB0wbAJesyPUtR7yGdyOR7aGbMQAX5Es9P56EDUEsvEQAC0NBj7JPqFuJEYXrvSCm5OPRmGaUQBswjkRxVB00mz8xhkFX',
            ),
            array(
                'id' => 144,
                'key' => 'enable_paymongo',
                'value' => '1',
            ),
            array(
                'id' => 145,
                'key' => 'paymongo_key',
                'value' => 'pk_test_iD6aYYm4yFuvkuisyU2PGSYH',
            ),
            array(
                'id' => 146,
                'key' => 'paymongo_secret',
                'value' => 'sk_test_oxD79bMKxb8sA47ZNyYPXwf3',
            ),
            array(
                'id' => 147,
                'key' => 'salon_app_name',
                'value' => 'Barber Shop +',
            ),
            array(
                'id' => 148,
                'key' => 'default_country_code',
                'value' => 'TG',
            ),
            array(
                'id' => 149,
                'key' => 'enable_otp',
                'value' => '1',
            ),
            array(
                'id' => 150,
                'key' => 'default_wallet_name',
                'value' => 'Igris',
            ),

            array(
                'id' => 151,
                'key' => 'owner_initial_amount',
                'value' => 1000,
            ),
            
            array(
                'id' => 152,
                'key' => 'customer_initial_amount',
                'value' => 500,
            ),
            array(
                'id' => 153,
                'key' => 'default_distance',
                'value' => 10,
            ),
            array(
                'id' => 154,
                'key' => 'app_default_wallet_id',
                'value' => '01194a4f-f302-47af-80b2-ceb2075d36dc',
            ),
            array(
                'id' => 155,
                'key' => 'booking_price',
                'value' => 200,
            ),
            array(
                'id' => 156,
                'key' => 'owner_partener_rewards',
                'value' => 0,
            ),
            array(
                'id' => 157,
                'key' => 'partener_rewards',
                'value' => 0,
            ),
            
            array(
                'id' => 158,
                'key' => 'referral_rewards',
                'value' => 0,
            ),
            array(
                'id' => 159,
                'key' => 'owner_referral_rewards',
                'value' => 0,
            ),
           
        ));


    }
}
