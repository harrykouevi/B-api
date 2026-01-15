@php
    $transaction_id = request()->query('transaction');
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement annulé</title>
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
            border: 2px solid #FF6B6B;
            padding: 2rem;
            border-radius: 10px;
            max-width: 400px;
            box-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
            text-align: center;
        }
        h1 {
            color: #FF6B6B;
        }
        p {
            margin: 1rem 0;
            line-height: 1.6;
        }
        .transaction-id {
            font-size: 0.9rem;
            color: #666;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
<div class="message-box">
    <h1>Paiement annulé</h1>
    <p>Vous avez annulé le paiement. Aucun montant n'a été débité.</p>
    <p>Vous pouvez réessayer votre paiement à tout moment depuis l'application.</p>
    @if($transaction_id)
        <div class="transaction-id">
            Transaction ID: {{ $transaction_id }}
        </div>
    @endif
</div>
</body>
</html>
