<?php
/*
 * File name: AppServiceProvider.php
 * Last modified: 2024.04.18 at 17:21:24
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Providers;

use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Stripe\Stripe;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        try {
            config(['mail.driver' => setting('mail_driver', 'smtp')]);
            config(['mail.host' => setting('mail_host', 'smtp.mailgun.org')]);
            config(['mail.port' => setting('mail_port', 587)]);
            config(['mail.encryption' => setting('mail_encryption', 'tls')]);
            config(['mail.username' => setting('mail_username')]);
            config(['mail.password' => setting('mail_password')]);
            config(['mail.from.address' => setting('mail_from_address')]);
            config(['mail.from.name' => setting('mail_from_name')]);

            config(['services.mailgun.domain' => setting('mailgun_domain')]);
            config(['services.mailgun.secret' => setting('mailgun_secret')]);

            config(['services.sparkpost.secret' => setting('sparkpost_secret')]);
            config(['services.sparkpost.options.endpoint' => setting('sparkpost_options_endpoint')]);

            config(['services.facebook.client_id' => setting('facebook_app_id')]);
            config(['services.facebook.client_secret' => setting('facebook_app_secret')]);
            config(['services.facebook.redirect' => url('login/facebook/callback')]);
            config(['services.twitter.client_id' => setting('twitter_app_id')]);
            config(['services.twitter.client_secret' => setting('twitter_app_secret')]);
            config(['services.twitter.redirect' => url('login/twitter/callback')]);
            config(['services.google.client_id' => setting('google_app_id')]);
            config(['services.google.client_secret' => setting('google_app_secret')]);
            config(['services.google.redirect' => url('login/google/callback')]);

            config(['services.stripe.key' => setting('stripe_key')]);
            config(['services.stripe.secret' => setting('stripe_secret')]);
            Stripe::setApiKey(setting('stripe_secret'));
            Stripe::setClientId(setting('stripe_key'));
            config(['services.razorpay.key' => setting('razorpay_key')]);
            config(['services.razorpay.secret' => setting('razorpay_secret')]);

            config(['services.fcm.key' => setting('fcm_key', '')]);

            config(['paypal.mode' => setting('paypal_mode', '0') != '0' ? 'live' : 'sandbox']);
            config(['paypal.currency' => Str::upper(setting('default_currency_code', 'USD'))]);

            config(['paypal.sandbox.username' => setting('paypal_username')]);
            config(['paypal.sandbox.password' => setting('paypal_password')]);
            config(['paypal.sandbox.secret' => setting('paypal_secret')]);
            config(['paypal.sandbox.app_id' => "APP-80W284485P519543T"]);

            config(['paypal.live.username' => setting('paypal_username')]);
            config(['paypal.live.password' => setting('paypal_password')]);
            config(['paypal.live.secret' => setting('paypal_secret')]);
            config(['paypal.live.app_id' => setting('paypal_app_id')]);

            config(['app.timezone' => setting('timezone', 'UTC')]);
            date_default_timezone_set(setting('timezone', 'UTC'));
        } catch (Exception $e) {

        }
    }
}
