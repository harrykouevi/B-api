@php
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    $transaction_id = request()->query('transaction');

    $status = 'unknown';
    $message = '';

    if ($transaction_id) {
        $response = Http::post('https://api-checkout.cinetpay.com/v2/payment/check', [
            'apikey' => config('services.cinetpay.api_key'),
            'site_id' => config('services.cinetpay.site_id'),
            'transaction_id' => $transaction_id,
        ]);

        if ($response->successful()) {
             Log::info('Page de retour', ['response' => $response]);
            $data = $response->json();
            Log::info('Page de retour 2', ['data' => $data]);
            if ($data['code'] === '00' && $data['data']['status'] === 'ACCEPTED') {
                $status = 'success';
                $message = 'Merci pour votre paiement. Votre wallet a été rechargé avec succès.';
            } elseif ($data['data']['status'] === 'REFUSED') {
                $status = 'error';
                $message = 'Paiement refusé. Veuillez réessayer.';
            } else {
                $status = 'error';
                $message = 'Paiement non accepté. Vérifiez les détails.';
            }
        } else {
            $status = 'error';
            $message = 'Erreur lors de la vérification du paiement.';
        }
    } else {
        $message = 'Aucun identifiant de transaction trouvé.';
    }
@endphp

        <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Retour de paiement</title>
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
            border: 2px solid #FFD700;
            padding: 2rem;
            border-radius: 10px;
            max-width: 400px;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            text-align: center;
        }
        h1 {
            color: #FFD700;
        }
    </style>
</head>
<body>
<div class="message-box">
    @if ($status === 'success')
        <h1>Paiement réussi</h1>
    @elseif ($status === 'error')
        <h1>Erreur de paiement</h1>
    @else
        <h1>Statut inconnu</h1>
    @endif
    <p>{{ $message }}</p>
</div>
</body>
</html>
