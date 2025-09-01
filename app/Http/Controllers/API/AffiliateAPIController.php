<?php
/*
 * File name: AffiliateAPIController.php
 * Last modified: 2025.02.07 at 08:21:42
 * Author: GaelLokossou - https://github.com/GaelLokossou
 * Copyright (c) 2025
 */

namespace App\Http\Controllers\API;

use App\Criteria\Affiliations\AffiliatesOfUserCriteria;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Repositories\AffiliateRepository;
use App\Repositories\ConversionRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use App\Repositories\UserRepository;


use phpDocumentor\Reflection\PseudoTypes\FloatValue;

use App\Services\PaymentService;
use App\Services\PartenerShipService;
use App\Services\WalletType;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Class AffiliateAPIController
 * @package App\Http\Controllers\API
 */
class AffiliateAPIController extends Controller
{
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /** @var  AffiliateRepository */
    private AffiliateRepository $affiliateRepository;

    /** @var  ConversionRepository */
    private ConversionRepository $conversionRepository;

   
   
  
      /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

      /**
     * @var PartenerShipService
     */
    private PartenerShipService $partenerShipService;

    public function __construct(UserRepository $userRepository,PaymentService $paymentService, PartenerShipService $partenerShipService, AffiliateRepository $affiliateRepo , ConversionRepository $conversionRepo )
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->affiliateRepository = $affiliateRepo;
        $this->conversionRepository = $conversionRepo;
        $this->paymentService =  $paymentService ;
        $this->partenerShipService =  $partenerShipService ;

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
        // $input['link']= 'affilate-link?ref=' . $encryptedReferralCode;
        $input['code']=  $code;
 
        try {
            $affiliate = $this->affiliateRepository->create($input);
            
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
    

    /**
     * Enables an authenticated user to be referred by another user through an referral code. 
     * Once the code is validated, the referrer and referred user receive benefits .
     * POST|HEAD 
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmConversion(string $affiliationCode_ , Request $request)
    {
        try {
            $affiliation =$this->affiliateRepository->findByField('code',$affiliationCode_)->first();
            if( is_null($affiliation) ) throw new InvalidArgumentException('referral do not exist');

            $conversion = $this->partenerShipService->proceedPartenerShip(auth()->user(),$affiliation) ;

            if( $conversion ){ 
                //recuperation du user a qui appartient le code
                $partner = $affiliation->user;
                if( $partner){ 
                    //si il est trouvé user a qui appartient le code recois son bunus
                    $amount =  auth()->user()->hasRole('customer') ? setting('partener_rewards') : setting('owner_partener_rewards');
                    $this->paymentService->createPayment($amount,setting('app_default_wallet_id'),$partner, WalletType::BONUS);

                }

            }
            return $this->sendResponse($conversion, __('lang.saved_successfully', ['operator' => __('lang.partener_ship')]));
        } catch (Exception $e) {
           
             // Gestion de l'exception
             Log::channel('listeners_transactions')->error('Erreur lors de l\'affiliation à l\'utilisateur #' , [
                'exception' => $e,
            ]);
            return $this->sendError($e->getMessage());
        }
        
    }
 

}
