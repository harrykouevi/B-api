<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Services\PaymentService;
use App\Services\PaymentType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class CinetpayAPIController extends Controller
{
    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    
    public function __construct(PaymentService $paymentService , UserRepository $userRepository )
    {
        parent::__construct();
        $this->paymentService =  $paymentService ;
        $this->userRepository = $userRepository;
    }

    public function notify(Request $request , int $user_id)
    {
        // $secretKey = config('services.cinetpay.secret'); // à mettre dans .env
        $secretKey = 'cinetpay.secret'; // à mettre dans .env

        // Étape 1: Extraire toutes les données nécessaires
        $fields = [
            'cpm_site_id', 'cpm_trans_id', 'cpm_trans_date', 'cpm_amount',
            'cpm_currency', 'signature', 'payment_method', 'cel_phone_num',
            'cpm_phone_prefixe', 'cpm_language', 'cpm_version',
            'cpm_payment_config', 'cpm_page_action', 'cpm_custom',
            'cpm_designation', 'cpm_error_message',
        ];

        $data = '';
        foreach ($fields as $field) {
            $data .= $request->input($field, '');
        }

        // Étape 2: Générer le token
        $generatedToken = hash_hmac('sha256', $data, $secretKey);

        // Étape 3: Récupérer le token envoyé par CinetPay (via header)
        $receivedToken = $request->header('x-token'); // CinetPay doit vous confirmer le nom exact de l’en-tête utilisé

        if (!hash_equals($generatedToken, $receivedToken)) {
            Log::warning('Webhook CinetPay: Token invalide', [
                'generated' => $generatedToken,
                'received' => $receivedToken
            ]);
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // 🔒 Token validé, traiter la notification ici
        Log::info('Webhook CinetPay reçu avec succès', $request->all());

        $transactionId = $request->input('cpm_trans_id');
        $siteId = $request->input('cpm_site_id');

        // Étape 2 : Vérifier avec CinetPay
        $response = Http::post('https://api-checkout.cinetpay.com/v2/payment/check', [
            'apikey' => env('CINETPAY_API_KEY'),
            'site_id' => $siteId,
            'transaction_id' => $transactionId,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if ($data['code'] == '00' && $data['data']['status'] == 'ACCEPTED') {
                
                $user = $this->userRepository->find($user_id) ;
                if( $user){ 
                
                    $amount =  $request->input('cpm_amount') ;
                    // Recharge du wallet client preocess
                    //enregistrer le paiement recu depujis l'exterieur
                    $this->paymentService->createPaymentLinkWithExternal($amount, $user , PaymentType::CREDIT) ;
                }
            }
        }

       


        return response()->json(['message' => 'Notification traitée']);
    }
}
