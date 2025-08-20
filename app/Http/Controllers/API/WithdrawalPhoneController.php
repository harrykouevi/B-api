<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalPhoneRequest;
use App\Models\WithdrawalPhone;
use App\Models\User;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class WithdrawalPhoneController extends Controller
{
    /**
     * Create a new controller instance.
     */
    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        $this->cinetPayService = $cinetPayService;
    

    }
    public function index(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        log::info('Récupération des numéros de téléphone de retrait pour l\'utilisateur', ['user_id' => $request->user_id]);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        return WithdrawalPhone::where('user_id',$request->user_id)->get();
    }


public function store(Request $request)
{
    Log::info('Début store withdrawal phone', ['request' => $request->all()]);

    $userId = $request->user_id;
    Log::info('user_id reçu', ['user_id' => $userId]);

    try {
        $user = User::findOrfail($userId);
        Log::info('Utilisateur trouvé', ['user' => $user]);
    } catch (\Exception $e) {
        Log::error('Utilisateur non trouvé', ['user_id' => $userId, 'error' => $e->getMessage()]);
        return response()->json(['error' => 'Utilisateur non trouvé'], 404);
    }

    $count = WithdrawalPhone::where('user_id', $user->id)->count();
    Log::info('Nombre de numéros existants', ['count' => $count]);
    if ($count >= 10) {
        Log::warning('Limite de numéros atteinte', ['user_id' => $userId]);
        return response()->json(['error' => 'Limite de 10 numéros atteinte'], 403);
    }

    $phone = new WithdrawalPhone();
    $phone->user_id = $userId;
    $phone->phone_number = $request->phone_number;
   

    $prefix = '228';
    $name = $user->name ?? 'Utilisateur';
    $surname = $user->name ?? 'Utilisateur';
    $email = $user->email ?? ('user' . $userId . '@charm.com');
    $rawNumber = preg_replace('/\D/', '', $phone->phone_number);

    // Si le numéro commence par "228", on l'enlève
    if (strpos($rawNumber, '228') === 0) {
        $rawNumber = substr($rawNumber, 3);
    }

    Log::info('Numéro nettoyé pour CinetPay', ['rawNumber' => $rawNumber]);


    Log::info('Préparation des données pour addContact', [
        'prefix' => $prefix,
        'phone' =>$rawNumber,
        'name' => $name,
        'surname' => $surname,
        'email' => $email
    ]);

    $result = $this->cinetPayService->addContact(
        $prefix,
        $rawNumber,
        $name,
        $surname,
        $email
    );
    Log::info('Résultat addContact', ['result' => $result]);
    if (!$result['success']) {
        Log::error('Erreur lors de l\'ajout du contact', ['response' => $result['response']]);
        return response()->json(['error' => 'Erreur lors de l\'ajout du contact'], 500);
    }else{
        Log::info('Contact ajouté avec succès', ['phone' => $phone->phone_number]);
        $phone->save();
        Log::info('Numéro enregistré', ['phone' => $phone]);
    }
    
    return response()->json([
        'success' => true,
        'data' => $phone,
        'cinetpay_result' => $result
    ], 201);
}

public function update(Request $request, $id)
{
    Log::info('Début update withdrawal phone', ['id' => $id, 'request' => $request->all()]);

    $phone = WithdrawalPhone::findOrFail($id);
    Log::info('Numéro trouvé pour update', ['phone' => $phone]);

    $phone->phone_number = $request->phone_number;
    $phone->save();

    Log::info('Numéro mis à jour', ['phone' => $phone]);

    return response()->json($phone);
}

    public function destroy($id)
    {
        $phone = WithdrawalPhone::findOrFail($id);
        $phone->delete();
        return response()->json(["success"=>true], 204);

    }
    
}