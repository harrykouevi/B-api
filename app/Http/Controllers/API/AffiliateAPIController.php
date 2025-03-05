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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Repositories\AffiliateRepository;
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

use App\Repositories\WalletRepository;


/**
 * Class AffiliateAPIController
 * @package App\Http\Controllers\API
 */
class AffiliateAPIController extends Controller
{
    /** @var  AffiliateRepository */
    private AffiliateRepository $affiliateRepository;

     /**
     * @var WalletRepository
     */
    private WalletRepository $walletRepository;
    public function __construct( AffiliateRepository $affiliateRepo ,WalletRepository $walletRepository )
    {
        parent::__construct();
        $this->affiliateRepository = $affiliateRepo;
        $this->walletRepository = $walletRepository;
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
            return $this->sendError('affiliate not found');
        }
        $this->filterModel($request, $affiliate);
        return $this->sendResponse($affiliate->toArray(), 'affiliate retrieved successfully');

    }


    /**
     * Create or generate  a newly created Affiliate in storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function generateLink(Request $request)
    {
        $input = $request->all();
        // $user = Auth::user(); // Utilisez auth()->user() au lieu de auth()->Auth::user()
        $input['user_id'] = Auth::id();
    
        // Vérifier si l'utilisateur a déjà un lien d'affiliation
        $existingAffiliate = $this->affiliateRepository->findByField('user_id', $input['user_id'])->first();
        if ($existingAffiliate) {
            return $this->sendResponse($existingAffiliate->toArray(), __('lang.updated_successfully', ['operator' => __('lang.address')]));
        }
 
        // Générer un code unique basé sur l'ID utilisateur
        $referralCode = 'REF' . $input['user_id'] . strtoupper(Str::random(4));
        // Crypter le mot de passe avant de l'assigner au lien
        $encryptedReferralCode = Hash::make($referralCode);
        // Génération du lien d'affiliation
        $input['link']= 'affilate-link?ref=' . $encryptedReferralCode;
        $input['code']=  $encryptedReferralCode;
 
        try {
            $affiliate = $this->affiliateRepository->create($input);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($affiliate->toArray(), __('lang.updated_successfully', ['operator' => __('lang.address')]));

    }

    public function trackConversion(Request $request)
    {
        $affiliationId = $request->query('affiliation_id');
        $affiliation =$this->affiliateRepository->find($affiliationId);

        $input = $request->all();
       
        // Enregistre le clic comme une conversion potentielle
        $affiliation->conversions()->create(['status' => 'pending']);
        
        // Enregistrer le clic dans la base de données
        $input['click'] =  $affiliation->click + 1 ;
        $affiliation =$this->affiliateRepository->update($input, $affiliation->id);

        // Rediriger vers l'application mobile avec le lien profond
        return redirect()->to('com.example.barbershop://affiliate-link?affiliate_link_id=' . $affiliationId );
    }
    
    public function confirmConversion(Request $request)
    {
        $affiliationId = $request->query('affiliation_id');
        try {
            $affiliation =$this->affiliateRepository->find($affiliationId);
            
            // Met à jour la conversion en tant que réussie
            $conversion = $affiliation->conversions()->where('status', 'pending')->first();
            if ($conversion) {
                $conversion->update([
                    'status' => 'success'
                ]);
                
                // Attribue la récompense au partenaire
                $this->rewardPartner($affiliation);
            }
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($conversion, __('lang.saved_successfully', ['operator' => __('lang.partener_ship')]));
    }
    
    private function rewardPartner(\App\Models\Affiliate $affiliation)
    {
        $partner = $affiliation->user;
        $wallet = $this->walletRepository->findByField('user_id', $partner->id )->first();
        
       

        // Code pour récompenser le partenaire
        // Par exemple, ajouter des points ou une commission
        $input = [];
        // $input['name'] = $request->get('name');
        // $input['currency'] = $currency;
        // $input['user_id'] = auth()->id();
        $input['balance'] = 0;
        // $input['enabled'] = 1;
        $wallet = $this->walletRepository->update($input, $wallet->id);
    }

}
