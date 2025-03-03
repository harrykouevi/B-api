<?php
/*
 * File name: AffiliateAPIController.php
 * Last modified: 2025.02.07 at 08:21:42
 * Author: GaelLokossou - https://github.com/GaelLokossou
 * Copyright (c) 2025
 */

namespace App\Http\Controllers\API;


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

/**
 * Class AffiliateAPIController
 * @package App\Http\Controllers\API
 */
class AffiliateAPIController extends Controller
{
    /** @var  AffiliateRepository */
    private AffiliateRepository $affiliateRepository;

    public function __construct(
        AffiliateRepository $affiliateRepo
        )
    {
        parent::__construct();
        $this->affiliateRepository = $affiliateRepo;
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
 
        try {
            $address = $this->affiliateRepository->create($input);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse($address->toArray(), __('lang.updated_successfully', ['operator' => __('lang.address')]));

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
        $affiliation =$this->affiliateRepository->find($affiliationId);
        
        // Met à jour la conversion en tant que réussie
        $affiliation->conversions()->where('status', 'pending')->update(['status' => 'success','user_id' => Auth()->id,]);
        
     
        // Met à jour la conversion en tant que réussie
        $conversion = $affiliation->conversions()->where('status', 'pending')->first();
        if ($conversion) {
            $conversion->update([
                'status' => 'success'
            ]);
            
            // Attribue la récompense au partenaire
            $this->rewardPartner($affiliation);
        }
    }
    
    private function rewardPartner($affiliation)
    {
        // Code pour récompenser le partenaire
        // Par exemple, ajouter des points ou une commission
        $partner = $affiliation->partner;
        $partner->points += 10; // Exemple de récompense
        $partner->save();
    }

}
