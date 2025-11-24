# Configuration PayDunya

Ce fichier documente la configuration PayDunya pour le projet.

## Vue d'ensemble

Le projet utilise **deux services PayDunya différents** :

1. **PayDunya Checkout (PAR)** - Pour augmenter le wallet (recharge)
2. **PayDunya Disburse (PER)** - Pour les retraits vers Mobile Money

---

## 1. PayDunya Checkout (PAR - Paiement Avec Redirection)

### Utilisation
Service utilisé pour **augmenter le wallet** en redirigeant l'utilisateur vers la page de paiement PayDunya où il peut payer par :
- Mobile Money (T-Money, Moov, etc.)
- Carte bancaire
- Autres moyens disponibles sur PayDunya

### Variables d'environnement

```bash
# Clés API PayDunya Checkout (PAR)
PAYDUNYA_CHECKOUT_MASTER_KEY=votre_master_key
PAYDUNYA_CHECKOUT_PUBLIC_KEY=votre_public_key
PAYDUNYA_CHECKOUT_PRIVATE_KEY=votre_private_key
PAYDUNYA_CHECKOUT_TOKEN=votre_token
PAYDUNYA_CHECKOUT_BASE_URL=https://app.paydunya.com/api/v1
PAYDUNYA_CHECKOUT_MODE=live  # 'test' ou 'live'

# Informations du magasin (affichées sur la page de paiement)
PAYDUNYA_STORE_NAME="Nom de votre application"
PAYDUNYA_STORE_TAGLINE="Votre slogan"
PAYDUNYA_STORE_PHONE="228XXXXXXXX"
PAYDUNYA_STORE_POSTAL_ADDRESS="Votre adresse"
PAYDUNYA_STORE_WEBSITE_URL=https://votresite.com
PAYDUNYA_STORE_LOGO_URL=https://votresite.com/logo.png

# URLs de callback (pour recevoir les notifications de paiement)
PAYDUNYA_CHECKOUT_CALLBACK_URL=https://votreapi.com/api/paydunya/checkout/callback
PAYDUNYA_CHECKOUT_RETURN_URL=https://votreapp.com/payment/success
PAYDUNYA_CHECKOUT_CANCEL_URL=https://votreapp.com/payment/cancel
```

### Flow du paiement Checkout (PAR)

1. **Frontend** → Appelle `/api/wallets/increase` avec le montant
2. **Backend** → Tente CinetPay en premier
3. **Si CinetPay échoue** → Fallback vers PayDunya Checkout
4. **Backend** → Crée une facture PayDunya et retourne `payment_url`
5. **Frontend** → Redirige l'utilisateur vers `payment_url`
6. **Utilisateur** → Paie sur la page PayDunya
7. **PayDunya** → Envoie une notification IPN vers `PAYDUNYA_CHECKOUT_CALLBACK_URL`
8. **Backend** → Reçoit la callback, vérifie le hash, crédite le wallet
9. **PayDunya** → Redirige l'utilisateur vers `PAYDUNYA_CHECKOUT_RETURN_URL` (succès) ou `PAYDUNYA_CHECKOUT_CANCEL_URL` (annulation)

### Vérification du hash (sécurité)

PayDunya envoie un hash SHA-512 de votre `MASTER_KEY` dans la callback pour vérifier que la requête provient bien de leurs serveurs.

```php
$receivedHash = $payload['data']['hash'];
$expectedHash = hash('sha512', $masterKey);
if ($receivedHash !== $expectedHash) {
    // Requête non autorisée
}
```

---

## 2. PayDunya Disburse (PER - Push/Décaissement)

### Utilisation
Service utilisé pour **les retraits** depuis le wallet vers les comptes Mobile Money des utilisateurs.

### Variables d'environnement

```bash
# Clés API PayDunya Disburse (PER)
PAYDUNYA_DISBURSE_MASTER_KEY=votre_master_key
PAYDUNYA_DISBURSE_PRIVATE_KEY=votre_private_key
PAYDUNYA_DISBURSE_TOKEN=votre_token
PAYDUNYA_DISBURSE_BASE_URL=https://app.paydunya.com/api/v2
PAYDUNYA_DISBURSE_CALLBACK_URL=https://votreapi.com/api/paydunya/disburse/callback
PAYDUNYA_DISBURSE_DEFAULT_MODE=t-money-togo  # Mode de retrait par défaut
```

### Modes de retrait supportés (Togo)

- `t-money-togo` - Pour Togocel/T-Money (préfixes: 90, 91, 92, 93, 70, 71, 72, 73)
- `moov-togo` - Pour Moov Togo (préfixes: 96, 97, 98, 99, 76, 77, 78, 79)

Le système détecte automatiquement le mode de retrait basé sur le numéro de téléphone.

### Flow du retrait Disburse (PER)

1. **Frontend** → Appelle `/api/wallets/withdraw` avec le montant et le numéro
2. **Backend** → Tente CinetPay en premier
3. **Si CinetPay échoue** → Fallback vers PayDunya Disburse
4. **Backend** → Détecte automatiquement le mode de retrait (t-money ou moov)
5. **Backend** → Crée une facture de décaissement (invoice)
6. **Backend** → Soumet la facture pour exécution
7. **PayDunya** → Traite le décaissement
8. **PayDunya** → Envoie une notification vers `PAYDUNYA_DISBURSE_CALLBACK_URL`
9. **Backend** → Met à jour le statut de la transaction

---

## Configuration minimale requise

Si vous utilisez les **mêmes clés** pour les deux services (recommandé pour la plupart des cas) :

```bash
# Clés communes
PAYDUNYA_MASTER_KEY=votre_master_key
PAYDUNYA_PUBLIC_KEY=votre_public_key
PAYDUNYA_PRIVATE_KEY=votre_private_key
PAYDUNYA_TOKEN=votre_token

# URLs de callback
PAYDUNYA_CHECKOUT_CALLBACK_URL=https://votreapi.com/api/paydunya/checkout/callback
PAYDUNYA_DISBURSE_CALLBACK_URL=https://votreapi.com/api/paydunya/disburse/callback
```

Le système utilisera automatiquement les clés communes si les clés spécifiques ne sont pas définies.

---

## Obtenir vos clés API PayDunya

1. Connectez-vous à votre compte PayDunya Business : https://app.paydunya.com
2. Allez dans **"Intégrez notre API"**
3. Cliquez sur **"Configurer une nouvelle application"**
4. Remplissez le formulaire
5. Récupérez vos clés :
   - **Master Key**
   - **Public Key**
   - **Private Key**
   - **Token**

### Mode Test vs Mode Production

- **Mode Test** : Utilisez les clés de test pour tester sans frais réels
- **Mode Production** : Activez le mode production dans votre compte PayDunya puis utilisez les clés de production

Pour passer en mode test :
```bash
PAYDUNYA_CHECKOUT_MODE=test
```

---

## Routes API

### Augmenter le wallet (Checkout)
```
POST /api/wallets/increase
```

Callback PayDunya Checkout :
```
POST /api/paydunya/checkout/callback
```

### Retirer du wallet (Disburse)
```
POST /api/wallets/withdraw
```

Callback PayDunya Disburse :
```
POST /api/paydunya/disburse/callback
```

---

## Documentation officielle PayDunya

- **API Checkout (PAR)** : https://paydunya.com/developers/php
- **API Disburse (PER)** : Documentation fournie par support PayDunya
- **Support** : support@paydunya.com

---

## Notes importantes

1. **Sécurité** : Ne jamais exposer vos clés API dans le code frontend
2. **Hash** : Toujours vérifier le hash dans les callbacks pour éviter les requêtes frauduleuses
3. **Logs** : Les logs détaillés sont disponibles dans `storage/logs/laravel.log`
4. **Test** : Testez toujours en mode test avant de passer en production
5. **Fallback** : Le système utilise CinetPay en priorité, PayDunya en fallback
