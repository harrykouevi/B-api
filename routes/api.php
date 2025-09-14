<?php
/*
 * File name: api.php
 * Last modified: 2022.10.16 at 19:34:07
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2022
 */

use App\Http\Controllers\API\AddressAPIController;
use App\Http\Controllers\API\PaygateController;
use App\Services\PaygateService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SalonAPIController;
use App\Http\Controllers\API\AffiliateAPIController ;
use App\Http\Controllers\API\BookingAPIController;
use App\Http\Controllers\API\CinetpayAPIController;
use App\Http\Controllers\API\CurrencyAPIController;
use App\Http\Controllers\API\ModuleAPIController;
use App\Http\Controllers\API\UserAPIController;
use App\Http\Controllers\API\WithdrawalPhoneController;
use App\Http\Controllers\API\SalonOwner\UserAPIController as UOwnerAPIController;
use Illuminate\Http\Request;
use App\Http\Controllers\API\WalletAPIController;
use App\Http\Controllers\API\PaymentAPIController;
use App\Http\Controllers\API\UploadAPIController;
use App\Http\Controllers\API\CinetpayTransferController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/test', function (Request $request) {
    dd(env('APP_LOCALE')) ;
})->name('test');

Route::prefix('salon_owner')->group(function () {
    // Route::post('login', 'API\SalonOwner\UserAPIController@login')->name('api.login');
    Route::post('register', [UOwnerAPIController::class,'register']);
    Route::post('v2/register', [UOwnerAPIController::class,'v2_register']);
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\SalonOwner\UserAPIController@user');
    Route::get('logout', 'API\SalonOwner\UserAPIController@logout');
    Route::get('settings', [UOwnerAPIController::class]);
    Route::get('translations', 'API\TranslationAPIController@translations');
    Route::get('supported_locales', 'API\TranslationAPIController@supportedLocales');
    Route::middleware('auth:api')->group(function () {
        Route::resource('salons', 'API\SalonOwner\SalonAPIController')->only(['index', 'show']);
        Route::get('e_services', 'API\SalonOwner\EServiceAPIController@index');
        Route::resource('availability_hours', 'API\AvailabilityHourAPIController')->only(['store', 'update', 'destroy']);
        Route::resource('awards', 'API\AwardAPIController')->only(['store', 'update', 'destroy']);
        Route::resource('experiences', 'API\ExperienceAPIController')->only(['store', 'update', 'destroy']);
        Route::get('salon_levels', 'API\SalonLevelAPIController@index');
        Route::get('taxes', 'API\SalonOwner\TaxAPIController@index');
        Route::get('employees', 'API\SalonOwner\UserAPIController@employees');
    });
});


Route::post('login', 'API\UserAPIController@login');
Route::post('recharge/callback/{user_id}', [CinetpayAPIController::class, 'notify']);
Route::post('paygate/callback', [PaygateController::class, 'handleCallback']);


Route::post('/cinetpay/transfer/webhook', [CinetpayAPIController::class,
    'handleTransferNotification'

])->name('cinetpay.transfer.webhook');


// Route pour le ping (GET)
Route::get('/cinetpay/transfer/webhook', [App\Http\Controllers\API\CinetpayAPIController::class, 'ping'])->name('cinetpay.transfer.webhook.ping');

Route::post('register', [UserAPIController::class, 'register']);
Route::post('v2/register', [UserAPIController::class, 'v2_register']);
Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
Route::get('user', 'API\UserAPIController@user');
Route::get('logout', 'API\UserAPIController@logout');
Route::get('settings', 'API\UserAPIController@settings');
Route::get('translations', 'API\TranslationAPIController@translations');
Route::get('supported_locales', 'API\TranslationAPIController@supportedLocales');
Route::get('modules', [ModuleAPIController ::class, 'index']);


Route::resource('salon_levels', 'API\SalonLevelAPIController');
Route::resource('salons', SalonAPIController::class)->only(['index', 'show']);
Route::resource('availability_hours', 'API\AvailabilityHourAPIController')->only(['index', 'show']);
Route::resource('awards', 'API\AwardAPIController')->only(['index', 'show']);
Route::resource('experiences', 'API\ExperienceAPIController')->only(['index', 'show']);

Route::resource('faq_categories', 'API\FaqCategoryAPIController');
Route::resource('faqs', 'API\FaqAPIController');
Route::resource('custom_pages', 'API\CustomPageAPIController');

Route::resource('categories', 'API\CategoryAPIController');

Route::resource('e_services', 'API\EServiceAPIController');
Route::resource('galleries', 'API\GalleryAPIController');
Route::get('salon_reviews/{id}', 'API\SalonReviewAPIController@show');
Route::get('salon_reviews', 'API\SalonReviewAPIController@index');

Route::resource('currencies', CurrencyAPIController::class);
Route::resource('slides', 'API\SlideAPIController')->except([
    'show'
]);
Route::resource('booking_statuses', 'API\BookingStatusAPIController')->except([
    'show'
]);
Route::resource('option_groups', 'API\OptionGroupAPIController');
Route::resource('options', 'API\OptionAPIController');

Route::get('affiliate/track-click/{affiliateLinkId}', [AffiliateAPIController::class, 'trackConversion']);

Route::middleware('auth:api')->group(function () {
    Route::get('affiliate', [AffiliateAPIController::class, 'show']);
    Route::post('affiliate/generate-link', [AffiliateAPIController::class, 'generateLink'])->name('affiliates.generate');
    Route::get('affiliate/confirm-conversion/{affiliateLinkId}', [AffiliateAPIController::class, 'confirmConversion'])->name('affiliates.confirm');;

    Route::post('/send-email-verification-otp', [UserAPIController::class, 'sendEmailVerificationOtp']);
    Route::post('/verify-email-otp', [UserAPIController::class, 'verifyEmailOtp']);
    
    Route::post('affiliate/conversion/{affiliateLinkId}', [AffiliateAPIController::class, 'confirmConversion']);

    Route::group(['middleware' => ['role:salon owner']], function () {
        Route::prefix('salon_owner')->group(function () {
            Route::post('users/{user}', 'API\UserAPIController@update');
            Route::get('dashboard', 'API\DashboardAPIController@provider');
            Route::resource('notifications', 'API\NotificationAPIController');
            Route::put('payments/{id}', 'API\PaymentAPIController@update')->name('payments.update');
        });
    });
    Route::resource('salons', SalonAPIController::class)->only([
        'store', 'update', 'destroy'
    ]);
    Route::post('uploads/store', [UploadAPIController::class ,'store']);
    Route::post('uploads/clear',  [UploadAPIController::class ,'clear']);
    Route::post('uploads/delete-by-url', [UploadAPIController::class, 'deleteByUrl']);
    Route::delete('uploads/delete-by-path', [UploadAPIController::class, 'deleteByPath']);
    Route::post('users/{user}', 'API\UserAPIController@update');
    Route::delete('users', 'API\UserAPIController@destroy');

    Route::get('payments/byMonth', 'API\PaymentAPIController@byMonth')->name('payments.byMonth');
    Route::post('payments/wallets/{id}', [PaymentAPIController::class ,'wallets'])->name('payments.wallets');
    Route::post('payments/cash', 'API\PaymentAPIController@cash')->name('payments.cash');
    Route::post('payments/mobile', 'API\PaymentAPIController@cash')->name('payments.mobile');
    Route::resource('payment_methods', 'API\PaymentMethodAPIController')->only([
        'index'
    ]);
    Route::post('salon_reviews', 'API\SalonReviewAPIController@store')->name('salon_reviews.store');

    Route::resource('categories', 'API\CategoryAPIController')->only(['store']);


    Route::resource('favorites', 'API\FavoriteAPIController');
    Route::resource('addresses', AddressAPIController::class);

    Route::get('notifications/count', 'API\NotificationAPIController@count');
    Route::resource('notifications', 'API\NotificationAPIController');
    Route::resource('bookings', BookingAPIController::class);

    Route::resource('earnings', 'API\EarningAPIController');

    Route::resource('salon_payouts', 'API\SalonPayoutAPIController');

    Route::resource('coupons', 'API\CouponAPIController')->except([
        'show'
    ]);
    Route::resource('wallets', WalletAPIController::class)->except([
        'show', 'create', 'edit' , 'store'
    ]);

    Route::post('defaut-wallets', [WalletAPIController::class, 'storeDefault'])->name('wallet.storedefault');
    Route::post('wallets/deposit', [WalletAPIController::class, 'deposit'])->name('wallet.deposit');
    Route::get('wallet_transactions', 'API\WalletTransactionAPIController@index')->name('wallet_transactions.index');

    Route::post('send-notification', [WalletAPIController::class, 'sendNotification'])->name('notifications.test');
    Route::post('recharge/', [WalletAPIController::class, 'increaseWallet'])->name('increase_wallet');
    Route::post('retrait/', [WalletAPIController::class, 'withdrawOnWallet'])->name('withdraw_on_wallet');
    // Historique des retraits
    Route::get('/wallets/withdrawals/history', [App\Http\Controllers\API\WalletAPIController::class, 'getWithdrawalHistory']);
    Route::get('/wallets/withdrawals/{id}', [App\Http\Controllers\API\WalletAPIController::class, 'getWithdrawalDetails']);

    // Routes to manage withdrawal phone numbers
    Route::get('withdrawal-phones', [WithdrawalPhoneController::class, 'index'])->name('withdrawal-phones.index');
    Route::post('withdrawal-phones', [WithdrawalPhoneController::class, 'store'])->name('withdrawal-phones.store');
    Route::put('withdrawal-phones/{id}', [WithdrawalPhoneController::class, 'update'])->name('withdrawal-phones.update');
    Route::delete('withdrawal-phones/{id}', [WithdrawalPhoneController::class, 'destroy'])->name('withdrawal-phones.destroy');

    // Report Routes
    Route::post('bookings/{id}/report', [BookingAPIController::class, 'report'])
        ->name('bookings.report');
    
    Route::get('bookings/{id}/report-history', [BookingAPIController::class, 'reportHistory'])
        ->name('bookings.report.history');
    
    Route::get('bookings/{id}/can-report', [BookingAPIController::class, 'canReport'])
        ->name('bookings.can.report');
    
    // Cancel routes
    Route::post('bookings/{id}/cancel', [BookingAPIController::class, 'cancel'])
        ->name('bookings.cancel');
    
    Route::get('bookings/my-cancellations', [BookingAPIController::class, 'myCancellations'])
        ->name('bookings.my.cancellations');
    
    Route::get('bookings/{id}/can-cancel', [BookingAPIController::class, 'canCancel'])
        ->name('bookings.can.cancel');
});
