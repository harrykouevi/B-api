@php
    use App\Services\PaydunyaService;
    use Illuminate\Support\Facades\Log;

    $referenceNumber = request()->query('reference') ?? request()->query('reference_number');
    $statusKey = 'unknown';
    $statusTitle = 'Statut inconnu';
    $message = '';

    if ($referenceNumber) {
        /** @var PaydunyaService $paydunyaService */
        $paydunyaService = app(PaydunyaService::class);
        $statusResponse = $paydunyaService->checkPaymentStatus($referenceNumber);
        Log::info('Consultation PayDunya depuis page de retour', [
            'reference' => $referenceNumber,
            'response' => $statusResponse,
        ]);

        if ($statusResponse['success']) {
            $gatewayStatus = strtolower($statusResponse['data']['status'] ?? 'pending');

            if ($gatewayStatus === 'completed') {
                $statusKey = 'success';
                $statusTitle = 'Paiement réussi';
                $message = 'Merci pour votre paiement. Votre wallet sera crédité sous peu.';
            } elseif ($gatewayStatus === 'pending') {
                $statusKey = 'pending';
                $statusTitle = 'Paiement en attente';
                $message = 'Votre paiement est en cours de traitement. Merci de patienter quelques instants.';
            } else {
                $statusKey = 'error';
                $statusTitle = 'Paiement échoué';
                $message = 'Le paiement a été annulé ou a échoué. Veuillez réessayer.';
            }
        } else {
            $statusKey = 'error';
            $statusTitle = 'Erreur de vérification';
            $message = $statusResponse['message'] ?? 'Impossible de vérifier le statut de la demande.';
        }
    } else {
        $message = 'Aucune référence PayDunya fournie.';
    }
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Retour PayDunya</title>
    <style>
        body {
            background-color: #ffffff;
            color: #000;
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .message-box {
            border: 2px solid #1dbf73;
            padding: 2rem;
            border-radius: 10px;
            max-width: 420px;
            box-shadow: 0 0 10px rgba(29, 191, 115, 0.35);
            text-align: center;
        }
        .message-box h1 {
            color: #1dbf73;
        }
        .message-box.error {
            border-color: #e74c3c;
            box-shadow: 0 0 10px rgba(231, 76, 60, 0.35);
        }
        .message-box.pending {
            border-color: #f1c40f;
            box-shadow: 0 0 10px rgba(241, 196, 15, 0.35);
        }
    </style>
</head>
<body>
<div class="message-box {{ $statusKey }}">
    <h1>{{ $statusTitle }}</h1>
    <p>{{ $message }}</p>
    @if($referenceNumber)
        <small>Référence PayDunya : {{ $referenceNumber }}</small>
    @endif
</div>
</body>
</html>
