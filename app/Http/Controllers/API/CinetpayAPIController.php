<?php

namespace App\Http\Controllers\API;

use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\NewReceivedPayment;
use App\Repositories\UserRepository;
use App\Services\PaymentService;
use App\Types\PaymentType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\CinetPayService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RechargePayment;
use App\Services\NotificationService;

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
        Log::info('Entrée dans notify', ['user_id' => $user_id, 'request' => $request->all()]);

        // $secretKey = config('services.cinetpay.secret'); // à mettre dans .env
        $secretKey = config('services.cinetpay.secret_key');

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
            'apikey' => config('services.cinetpay.api_key'),
            'site_id' => config('services.cinetpay.site_id'),
            'transaction_id' => $transactionId,
        ]);



        if ($response->successful()) {
            $data = $response->json();
            Log::info('Statut de la transaction', [
                'status' => $data['data']['status'],
                'message' => $request->input('cpm_error_message')
            ]);
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


    public function handleTransferNotification(Request $request, int $userId): JsonResponse
    {
        try {
            Log::info('Webhook CinetPay - Notification de transfert reçue', [
                'user_id' => $userId,
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Extraire les données de la requête
            $transactionId = $request->input('transaction_id');
            $clientTransactionId = $request->input('client_transaction_id');
            $lot = $request->input('lot');
            $amount = $request->input('amount');
            $receiver = $request->input('receiver');
            $sendingStatus = $request->input('sending_status');
            $comment = $request->input('comment');
            $treatmentStatus = $request->input('treatment_status');
            $operatorTransactionId = $request->input('operator_transaction_id');
            $validatedAt = $request->input('validated_at');

            // Vérifier que l'identifiant client est présent
            if (!$clientTransactionId) {
                Log::warning('Webhook CinetPay - Identifiant client manquant');
                return response()->json(['error' => 'Client transaction ID is required'], 400);
            }

            // Utiliser la méthode du service pour extraire l'ID
            $walletTransactionId = app(CinetPayService::class)->extractWalletTransactionId($clientTransactionId);

            if (!$walletTransactionId) {
                Log::warning('Webhook CinetPay - Format d\'identifiant client invalide', [
                    'client_transaction_id' => $clientTransactionId
                ]);
                return response()->json(['error' => 'Invalid client transaction ID format'], 400);
            }

            // Trouver la transaction wallet correspondante
            $walletTransaction = WalletTransaction::find($walletTransactionId);

            if (!$walletTransaction) {
                Log::warning('Webhook CinetPay - Transaction wallet non trouvée', [
                    'wallet_transaction_id' => $walletTransactionId,
                    'client_transaction_id' => $clientTransactionId
                ]);
                return response()->json(['error' => 'Wallet transaction not found'], 404);
            }

            // Mettre à jour la transaction avec les informations de CinetPay
            $description = $walletTransaction->description;
            $description .= " | CinetPay ID: {$transactionId}";
            $description .= " | Status: {$treatmentStatus}";
            $description .= " | Sending: {$sendingStatus}";
            if ($lot) {
                $description .= " | Lot: {$lot}";
            }
            if ($operatorTransactionId) {
                $description .= " | Operator ID: {$operatorTransactionId}";
            }

            $updateData = [
                'description' => $description,
            ];

            // Mettre à jour le statut en fonction des informations reçues
            if ($treatmentStatus === 'VAL' && $sendingStatus === 'CONFIRM') {
                // Transfert validé et confirmé
                $updateData['status'] = WalletTransaction::STATUS_COMPLETED;
                
                // Débiter le wallet si ce n'est pas déjà fait
                $wallet = $walletTransaction->wallet;
                if ($wallet) {
                    // Vérifier que le wallet a suffisamment de fonds et que la transaction n'est pas déjà débitée
                    if ($wallet->balance >= $walletTransaction->amount && 
                        $walletTransaction->status !== WalletTransaction::STATUS_COMPLETED) {
                        $wallet->decrement('balance', $walletTransaction->amount);
                        Log::info('Wallet débité avec succès', [
                            'wallet_id' => $wallet->id,
                            'amount' => $walletTransaction->amount,
                            'new_balance' => $wallet->balance - $walletTransaction->amount
                        ]);
                    } else {
                        Log::warning('Impossible de débiter le wallet', [
                            'wallet_id' => $wallet->id,
                            'balance' => $wallet->balance,
                            'amount' => $walletTransaction->amount,
                            'transaction_status' => $walletTransaction->status
                        ]);
                    }
                }
            } elseif (in_array($treatmentStatus, ['REJECT', 'CANCEL'])) {
                // Transfert rejeté ou annulé
                $updateData['status'] = WalletTransaction::STATUS_REJECTED;

                // Créditer à nouveau le wallet utilisateur seulement si le statut change
                if ($walletTransaction->status !== WalletTransaction::STATUS_REJECTED) {
                    $wallet = $walletTransaction->wallet;
                    if ($wallet) {
                        $wallet->increment('balance', $walletTransaction->amount);
                        Log::info('Wallet recrédité', [
                            'wallet_id' => $wallet->id,
                            'amount' => $walletTransaction->amount
                        ]);
                    }
                }
            } elseif ($treatmentStatus === 'NEW') {
                // Transfert en attente de confirmation
                $updateData['status'] = WalletTransaction::STATUS_PENDING;
            }

            // Mettre à jour la transaction
            $walletTransaction->update($updateData);
            Log::info('Transaction wallet mise à jour', [
                'wallet_transaction_id' => $walletTransactionId,
                'update_data' => $updateData
            ]);
            
            // Envoyer une notification à l'utilisateur
            try {
                $wallet = $walletTransaction->wallet;
                if ($wallet) {
                    // TODO: revoir le parametre envoyé à NewReceivedPayment
                    NotificationService::notify([$wallet->user], new NewReceivedPayment($transactionId, $wallet));
                }
            } catch(\Exception $e) {
                Log::error("Erreur lors de l'envoi de la notification", [
                    'wallet_transaction_id' => $walletTransactionId,
                    'error' => $e->getMessage()
                ]);
            }

            // Log de la mise à jour
            Log::info('Webhook CinetPay - Transaction wallet mise à jour', [
                'wallet_transaction_id' => $walletTransactionId,
                'status' => $walletTransaction->status,
                'treatment_status' => $treatmentStatus,
                'sending_status' => $sendingStatus
            ]);

            // Retourner une réponse de succès
            return response()->json([
                'success' => 'Notification traitée avec succès',
                'transaction_id' => $transactionId,
                'client_transaction_id' => $clientTransactionId
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du webhook CinetPay', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Erreur lors du traitement de la notification',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Méthode pour vérifier que l'URL est accessible (ping)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ping(Request $request, int $userId): JsonResponse
    {
        Log::info('Webhook CinetPay - Ping reçu', [
            'user_id' => $userId,
            'method' => $request->method(),
            'ip' => $request->ip()
        ]);

        return response()->json(['status' => 'ok'], 200);
    }

}
