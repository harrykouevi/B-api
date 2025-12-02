<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalPhoneRequest;
use App\Models\WithdrawalPhone;
use App\Models\User;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WithdrawalPhoneController extends Controller
{
    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        $this->cinetPayService = $cinetPayService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->user_id);
        Log::info('Récupération des numéros de téléphone de retrait pour l\'utilisateur', ['user_id' => $request->user_id]);

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json(WithdrawalPhone::where('user_id', $request->user_id)->get());
    }

    public function store(Request $request): JsonResponse
    {
        Log::info('Début store withdrawal phone', ['request' => $request->all()]);

        $userId = $request->user_id;
        Log::info('user_id reçu', ['user_id' => $userId]);

        try {
            $user = User::findOrFail($userId);
            Log::info('Utilisateur trouvé', ['user' => $user]);
        } catch (\Exception $e) {
            Log::error('Utilisateur non trouvé', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $count = WithdrawalPhone::where('user_id', $user->id)->count();
        Log::info('Nombre de numéros existants', ['count' => $count]);

        if ($count >= 5) {
            Log::warning('Limite de numéros atteinte', ['user_id' => $userId]);
            return response()->json(['error' => 'Limite de 5 numéros atteinte'], 403);
        }

        // Créer le numéro de téléphone
        $phone = new WithdrawalPhone();
        $phone->user_id = $userId;
        $phone->phone_number = $request->phone_number;
        $phone->account_type = $request->account_type; // yas ou moov (nullable)
        $phone->is_sync_cinetpay = false; // Par défaut non synchronisé

        // Tenter la synchronisation avec CinetPay
        $syncResult = $this->attemptCinetPaySync($phone, $user);

        // Enregistrer le numéro même si la sync échoue
        $phone->is_sync_cinetpay = $syncResult['success'];
        $phone->save();
        Log::info('Numéro enregistré', ['phone' => $phone, 'sync_success' => $syncResult['success']]);

        return response()->json([
            'success' => true,
            'data' => $phone,
            'is_sync_cinetpay' => $syncResult['success'],
            'sync_message' => $syncResult['message'],
            'cinetpay_result' => $syncResult['result'] ?? null,
        ], 201);
    }

    /**
     * Resynchroniser un numéro avec CinetPay
     */
    public function resync(Request $request, $id): JsonResponse
    {
        Log::info('Début resync withdrawal phone', ['id' => $id]);

        $phone = WithdrawalPhone::findOrFail($id);

        // Vérifier que le numéro appartient à l'utilisateur
        if ($phone->user_id != $request->user_id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $user = User::findOrFail($phone->user_id);

        // Tenter la synchronisation
        $syncResult = $this->attemptCinetPaySync($phone, $user);

        // Mettre à jour le statut de synchronisation
        $phone->is_sync_cinetpay = $syncResult['success'];
        $phone->save();

        Log::info('Resync terminée', ['phone_id' => $id, 'sync_success' => $syncResult['success']]);

        return response()->json([
            'success' => true,
            'data' => $phone,
            'is_sync_cinetpay' => $syncResult['success'],
            'sync_message' => $syncResult['message'],
            'cinetpay_result' => $syncResult['result'] ?? null,
        ]);
    }

    /**
     * Tente de synchroniser un numéro avec CinetPay
     */
    private function attemptCinetPaySync(WithdrawalPhone $phone, User $user): array
    {
        $prefix = '228';
        $name = $user->name ?? 'Utilisateur';
        $surname = $user->name ?? 'Utilisateur';
        $email = $user->email ?? 'bhelpconsulting@gmail.com';
        $rawNumber = preg_replace('/\D/', '', $phone->phone_number);

        // Si le numéro commence par "228", on l'enlève
        if (strpos($rawNumber, '228') === 0) {
            $rawNumber = substr($rawNumber, 3);
        }

        Log::info('Numéro nettoyé pour CinetPay', ['rawNumber' => $rawNumber]);

        Log::info('Préparation des données pour addContact', [
            'prefix' => $prefix,
            'phone' => $rawNumber,
            'name' => $name,
            'surname' => $surname,
            'email' => $email
        ]);

        try {
            $result = $this->cinetPayService->addContact(
                $prefix,
                $rawNumber,
                $name,
                $surname,
                $email
            );
            Log::info('Résultat addContact', ['result' => $result]);

            if ($result['success']) {
                Log::info('Contact ajouté avec succès à CinetPay', ['phone' => $phone->phone_number]);
                return [
                    'success' => true,
                    'message' => 'Numéro synchronisé avec CinetPay',
                    'result' => $result,
                ];
            } else {
                Log::warning('Échec de la synchronisation CinetPay', ['response' => $result['response'] ?? null]);
                return [
                    'success' => false,
                    'message' => 'Échec de la synchronisation avec CinetPay. Vous pourrez resynchroniser plus tard.',
                    'result' => $result,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de la synchronisation CinetPay', [
                'error' => $e->getMessage(),
                'phone' => $phone->phone_number,
            ]);
            return [
                'success' => false,
                'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage(),
                'result' => null,
            ];
        }
    }

public function update(Request $request, $id): JsonResponse
{
    Log::info('Début update withdrawal phone', ['id' => $id, 'request' => $request->all()]);

    $phone = WithdrawalPhone::findOrFail($id);
    Log::info('Numéro trouvé pour update', ['phone' => $phone]);

    $phone->phone_number = $request->phone_number;

    // Mettre à jour le type de compte si fourni
    if ($request->has('account_type')) {
        $phone->account_type = $request->account_type;
    }

    $user = User::findOrFail($phone->user_id);

    // Tenter la synchronisation avec CinetPay
    $syncResult = $this->attemptCinetPaySync($phone, $user);

    // Mettre à jour le statut de synchronisation et sauvegarder
    $phone->is_sync_cinetpay = $syncResult['success'];
    $phone->save();
    Log::info('Numéro mis à jour', ['phone' => $phone, 'sync_success' => $syncResult['success']]);

    return response()->json([
        'success' => true,
        'data' => $phone,
        'is_sync_cinetpay' => $syncResult['success'],
        'sync_message' => $syncResult['message'],
        'cinetpay_result' => $syncResult['result'] ?? null,
    ]);
}

    public function destroy($id)
    {
        $phone = WithdrawalPhone::findOrFail($id);
        $phone->delete();
        return response()->json(["success"=>true], 204);

    }
    
}