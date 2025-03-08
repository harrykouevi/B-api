<?php
/*
 * File name: AffiliateAPIController.php
 * Last modified: 2025.02.07 at 08:21:42
 * Author: GaelLokossou - https://github.com/GaelLokossou
 * Copyright (c) 2025
 */

namespace App\Http\Controllers\API;

use App\Criteria\Affiliations\AffiliatesOfUserCriteria;
use App\Criteria\Bookings\BookingsOfUserCriteria;
use App\Criteria\Coupons\ValidCriteria;
use App\Events\BookingChangedEvent;
use App\Events\BookingStatusChangedEvent;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Notifications\NewReceivedPayment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Repositories\AffiliateRepository;
use App\Repositories\ConversionRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\PaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

use App\Repositories\CurrencyRepository;


use App\Repositories\WalletRepository;
use phpDocumentor\Reflection\PseudoTypes\FloatValue;

use App\Services\PaymentService;


/**
 * Class AffiliateAPIController
 * @package App\Http\Controllers\API
 */
class AffiliateAPIController extends Controller
{
    /** @var  AffiliateRepository */
    private AffiliateRepository $affiliateRepository;

    /** @var  ConversionRepository */
    private ConversionRepository $conversionRepository;

    /** @var  WalletRepository */
    private WalletRepository $walletRepository;

    /**  @var  CurrencyRepository */
    private CurrencyRepository $currencyRepository;

    /** @var  PaymentRepository */
    private PaymentRepository $paymentRepository;
        
      /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService, PaymentRepository $paymentRepository, AffiliateRepository $affiliateRepo ,WalletRepository $walletRepository , ConversionRepository $conversionRepo ,CurrencyRepository $currencyRepository)
    {
        parent::__construct();
        $this->affiliateRepository = $affiliateRepo;
        $this->conversionRepository = $conversionRepo;
        $this->walletRepository = $walletRepository;
        $this->currencyRepository = $currencyRepository;
        $this->paymentRepository = $paymentRepository;
        $this->paymentService =  $paymentService ;

    }

    
     /**
     * Display the specified Booking.
     * GET|HEAD /bookings/{id}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show( Request $request): JsonResponse
    {
        try {
            $this->affiliateRepository->pushCriteria(new RequestCriteria($request));
            $this->affiliateRepository->pushCriteria(new AffiliatesOfUserCriteria(auth()->id()));
            $this->affiliateRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $affiliate = $this->affiliateRepository->first();
        if (empty($affiliate)) {
            return $this->sendError('affiliate not found',404);
        }
        $this->filterModel($request, $affiliate);
        return $this->sendResponse($affiliate->toArray(), 'affiliate retrieved successfully');

    }


    /**
     * Create or generate  a newly created Affiliate in storage.
     * POST|HEAD /affilate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateLink(Request $request): JsonResponse
    {
        $input = $request->all();
        // $user = Auth::user(); // Utilisez auth()->user() au lieu de auth()->Auth::user()
        $input['user_id'] = Auth::id();
    
        // Vérifier si l'utilisateur a déjà un lien d'affiliation
        $existingAffiliate = $this->affiliateRepository->findByField('user_id', $input['user_id'])->first();
        if ($existingAffiliate) {
            return $this->sendResponse($existingAffiliate->toArray(), __('lang.updated_successfully', ['operator' => __('lang.address')]));
        }

        $code = $this->getdigits(Auth::id()) ;
 
        // Générer un code unique basé sur l'ID utilisateur
        $referralCode = 'REF' . $input['user_id'] . strtoupper(Str::random(4));
        // Crypter le mot de passe avant de l'assigner au lien
        $encryptedReferralCode = Hash::make($referralCode);
        // Génération du lien d'affiliation
        $input['link']= 'affilate-link?ref=' . $encryptedReferralCode;
        $input['code']=  $code;
 
        try {
            $affiliate = $this->affiliateRepository->create($input);
            // $payment = $this->paymentRepository->create($input['payment']);
            // $booking = $this->bookingRepository->update(['payment_id' => $payment->id], $input['id']);
            //Notification::send($booking->salon->users, new NewReceivedPayment($payment));

        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($affiliate->toArray(), __('lang.updated_successfully', ['operator' => __('lang.address')]));

    }

    /**
     * 
     *
     * @param $nombre
     * @return int
     */
    private function getdigits($nombre) {

        // Vérifier si l'entrée est un nombre
        if (!is_numeric($nombre)) {
            echo "Veuillez entrer un nombre valide.\n";
            exit;
        }
        // Créer le tableau de chiffres
        $chiffres = str_split((string)$nombre);

        // Générer un nombre aléatoire de 3 chiffres en utilisant les chiffres
        // Assurez-vous d'avoir au moins 3 chiffres
        if (count($chiffres) < 3) {
            // Répéter les chiffres pour en avoir au moins 3
            while (count($chiffres) < 3) {
                $chiffres = array_merge($chiffres, $chiffres);
            }
        }

        // Mélanger les chiffres
        shuffle($chiffres);

        // Prendre les 3 premiers chiffres mélangés
        $nombre3Chiffres = (int)implode('', array_slice($chiffres, 0, 3));

        // Générer un nombre aléatoire de 5 chiffres
        $nombre5Chiffres = rand(10000, 99999);

        // Concaténer les nombres avec le nombre initial
        return (string)$nombre . $nombre3Chiffres . $nombre5Chiffres;

        
    }

    public function trackConversion(Request $request)
    {
        $affiliationCode_ = $request->query('affiliation_code');
        $affiliation =$this->affiliateRepository->find($affiliationCode_);

        $input = $request->all();
       
        // Enregistre le clic comme une conversion potentielle
        $affiliation->conversions()->create(['status' => 'pending']);
        
        // Enregistrer le clic dans la base de données
        $input['click'] =  $affiliation->click + 1 ;
        $affiliation =$this->affiliateRepository->update($input, $affiliation->id);

        // Rediriger vers l'application mobile avec le lien profond
        return redirect()->to('com.example.barbershop://affiliate-link?affiliate_link_id=' . $affiliationCode_ );
    }
    
    public function confirmConversion(Request $request)
    {
        $affiliationCode_ = $request->query('affiliation_code');
        try {
            $affiliation =$this->affiliateRepository->findByField('code',$affiliationCode_)->first();
            if ($affiliation == Null )  return $this->sendError("unprocessable partenership",404);
            if (auth()->id() == $affiliation->user_id) return $this->sendError("unprocessable partenership",404);

            // Met à jour la conversion en tant que réussie
            //$conversion = $affiliation->conversions()->where('status', 'pending')->first();
            $conversion = $this->conversionRepository->create([
                'affiliate_id' => $affiliation->id ,
                'affiliation' => $affiliation ,
                'status' => 'success'
            ]);
            // $conversion->update();
            
            // Attribue la récompense au partenaire
            $this->rewardPartner($affiliation , 500);
            return $this->sendResponse($conversion, __('lang.saved_successfully', ['operator' => __('lang.partener_ship')]));
        } catch (Exception $e) {
           
            return $this->sendError($e->getMessage());
        }
        
    }
    
    private function rewardPartner(\App\Models\Affiliate $affiliation , int $amout)
    {
        $partner = $affiliation->user;
        if($partner){ 
            // $wallet = $this->walletRepository->findByField('user_id', $partner->id )->first();
            
            // if($wallet){
        
                // Code pour récompenser le partenaire
                // Par exemple, ajouter des points ou une commission
                // $input = [];
                // $wallet->balance += $amout;
                // $wallet = $this->walletRepository->update($input, $wallet->id);

                $this->paymentService->createPayment($partner ,50);
            // }else{
            //     $currency = $this->currencyRepository->findWithoutFail(setting('default_currency_id'));
            //     if (empty($currency)) {
            //         return $this->sendError('Default Currency not found');
            //     }
            //     $input = [];
            //     $input['name'] = setting('default_wallet_name')?? "-";
            //     $input['currency'] = $currency;
            //     $input['user_id'] = $partner->id;
            //     $input['balance'] = $amout;
            //     $input['enabled'] = 1;
            //     $wallet = $this->walletRepository->create($input);
            //     createWallet($partner, setting() ) ;
            // }
        }
    }

}
