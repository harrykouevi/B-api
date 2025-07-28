<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Retour de paiement</title>
    <style>
        body {
            background-color: #ffffff; /* blanc */
            color: #000000; /* noir */
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .message-box {
            border: 2px solid #FFD700; /* doré */
            padding: 2rem;
            border-radius: 10px;
            max-width: 400px;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        h1 {
            color: #FFD700; /* doré */
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
<div class="message-box">
    @if(request()->query('status') === 'success')
        <h1>Paiement réussi</h1>
        <p>Merci pour votre paiement. Votre wallet a été rechargé avec succès.</p>
    @elseif(request()->query('status') === 'error')
        <h1>Erreur lors du paiement</h1>
        <p>{{ request()->query('message', 'Une erreur est survenue lors du traitement de votre paiement.') }}</p>
    @else
        <h1>Statut inconnu</h1>
        <p>Le statut du paiement n’a pas pu être déterminé.</p>
    @endif
</div>
</body>
</html>
