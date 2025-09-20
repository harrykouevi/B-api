<?php
/*
 * File name: PartenerShipService.php
 * Last modified: 2025.03.06 at 11:21:24
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2024
 */

namespace App\Services;

use App\Events\DoPaymentEvent;
use App\Models\Affiliate;
use App\Models\Conversion;
use App\Repositories\AffiliateRepository;
use App\Repositories\WalletRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\ConversionRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Exception;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewReceivedPayment;
use App\Notifications\NewDebitPayment ;
use App\Notifications\StatusChangedPayment;
use App\Types\WalletType;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\Cast\Double;

class PartenerShipService
{
    private $affiliateRepository;
    private $walletRepository;
    private $currencyRepository;
    private $conversionRepository;
    private $paymentRepository;
    
    private $currency ;

    public function __construct(
        AffiliateRepository $affiliateRepository,
        WalletRepository $walletRepository,
        CurrencyRepository $currencyRepository,
        ConversionRepository $conversionRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->affiliateRepository = $affiliateRepository;
        $this->walletRepository = $walletRepository;
        $this->conversionRepository = $conversionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->currencyRepository = $currencyRepository ;
        $this->currency = $this->currencyRepository->findWithoutFail(setting('default_currency_id'));
    }


    /**
    * get Affiliation .
    * 
    * @param String $affiliationCode_
    * @return Affiliate|Null
    */
    public function find(String $affiliationCode_) : Affiliate | Null
    {
        return $this->affiliateRepository->findByField('code',$affiliationCode_)->first();
    }



    /**
     * Handles the partnership process for a user with an optional affiliate.
     *
     * @param User $user The user attempting to be referred.
     * @param Affiliate $affiliation The affiliate
     * @return Conversion|null Returns the conversion if successful, or null otherwise.
     */
    public function proceedPartenerShip(User $user , Affiliate $affiliation ) : Conversion | Null
    {
        
        if ($user == Null )  return Null ;
        if ($affiliation == Null ) throw new \Exception("unprocessable partenership") ;
        
        // Increment le nombre de fois que le code d'affiliation à tenté d'etre utilisé
        $input['click'] =  $affiliation->click + 1 ;
        $affiliation =$this->affiliateRepository->update($input, $affiliation->id);

        if ($user!= Null && $user->sponsorship_at != Null )  throw new \Exception("already get sponsored");
        if ($user->id == $affiliation->user_id) throw new \Exception("unprocessable partenership") ;
        
        
        // Creer une conversion en tant que réussie
        $conversion = $this->conversionRepository->create([
            'affiliate_id' => $affiliation->id ,
            'affiliation' => $affiliation ,
            'status' => 'success'
        ]);

        // Met à jour les données de l'utilisateur afin de pouvoir vérifier ultérieurement s'il a déjà été parrainé
        $user->update([
            'sponsorship' => $affiliation,
            'sponsorship_at' => now(),
        ]);


        // Attribue la récompense à l'utilisateur qui a utilisé un code d'affiliation
        $amount = $user->hasRole('customer') ? setting('referral_rewards') : setting('owner_referral_rewards') ;
        $paymentInfo = ["amount"=> $amount,"payer_wallet"=>setting('app_default_wallet_id'), "user"=>$user , "walletType"=> WalletType::BONUS] ;
        Log::info('about to be patronize with '.$amount) ;
        event(new DoPaymentEvent($paymentInfo));

        //recuperation du user a qui appartient le code
        $partner = $affiliation->user;
        if( $partner){ 
            //si il est trouvé user a qui appartient le code recois son bunus
            $amount =  $partner->hasRole('customer') ? setting('partener_rewards') : setting('owner_partener_rewards');
            // $this->paymentService->createPayment($amount,setting('app_default_wallet_id'),$partner, WalletType::BONUS);
            
            $paymentInfo = ["amount"=> $amount,"payer_wallet"=>setting('app_default_wallet_id'), "user"=>$partner , "walletType"=> WalletType::BONUS] ;
            event(new DoPaymentEvent($paymentInfo));
        }
        
        return  $conversion ;
        
    }


    // private function rewardPartner(\App\Models\Affiliate $affiliation , int $amout)
    // {
    //     $partner = $affiliation->user;
    //     if($partner){ 
           
    //             $this->paymentService->createPayment(50,setting('app_default_wallet_id'),$partner );
           
    //     }
    // }


}
