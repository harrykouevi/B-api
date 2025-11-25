# Guide des Logs PayDunya Disbursement (PER)

Ce guide vous aide à suivre et déboguer le processus de retrait PayDunya (Push/Décaissement) dans les logs.

## 📍 Où trouver les logs ?

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Filtrer uniquement les logs PayDunya PER (Disbursement)
tail -f storage/logs/laravel.log | grep "PayDunya PER"

# Filtrer uniquement les logs du callback PER
tail -f storage/logs/laravel.log | grep "PayDunya Callback PER"
```

---

## 🎯 Flow complet des logs (Retrait)

### **Étape 1 : Tentative CinetPay Transfer (si activé)**

```
CinetPay transfer request/response logs...
Si échec → Passage à PayDunya PER
```

### **Étape 2 : Création de la facture de décaissement**

```
🔵 [PayDunya PER] Début createInvoice
   ├─ account_alias: 90123456
   ├─ amount: 5000
   ├─ withdraw_mode: t-money-togo
   └─ has_disburse_id: true

🟢 [PayDunya PER] Credentials OK, préparation du payload

📝 [PayDunya PER] Disburse ID ajouté
   └─ disburse_id: withdrawal_123456

📤 [PayDunya PER] Création de la facture de décaissement
```

### **Étape 3 : Requête HTTP vers PayDunya (Get Invoice)**

```
🌐 [PayDunya PER] POST Request Details
   ├─ url: "https://app.paydunya.com/api/v2/disburse/get-invoice"
   ├─ endpoint: "/disburse/get-invoice"
   ├─ payload_keys: ["account_alias", "amount", "withdraw_mode", "callback_url", "disburse_id"]
   └─ headers_present: ["Content-Type", "PAYDUNYA-MASTER-KEY", ...]

📋 [PayDunya PER] Payload complet
   └─ payload: {...}  // Détails complets en mode debug
```

### **Étape 4 : Réponse de l'API (Token obtenu)**

```
📥 [PayDunya PER] POST Response Received
   ├─ status_code: 200
   ├─ success: true
   └─ response_code: "00"

📋 [PayDunya PER] Response body complet
   └─ body: {"response_code": "00", "disburse_token": "hwTHAS0WvTmTaYT2zDoO"}

✅ [PayDunya PER] Request Successful
   └─ response_code: "00"

🎉 [PayDunya PER] Facture de décaissement créée avec succès
   └─ disburse_invoice: "hwTHAS0WvTmTaYT2zDoO"
```

### **Étape 5 : Soumission de la facture pour exécution**

```
🚀 [PayDunya PER] Début submitInvoice
   ├─ disburse_invoice: "hwTHAS0WvTmTaYT2zDoO"
   └─ has_disburse_id: true

📤 [PayDunya PER] Soumission de la facture pour exécution

🌐 [PayDunya PER] POST Request Details
   ├─ url: "https://app.paydunya.com/api/v2/disburse/submit-invoice"
   └─ endpoint: "/disburse/submit-invoice"

📥 [PayDunya PER] POST Response Received
   ├─ status_code: 200
   ├─ success: true
   └─ response_code: "00"

✅ [PayDunya PER] Facture soumise avec succès
   ├─ status: "success" (ou "pending")
   └─ transaction_id: "TFA-TX-37XqPpVCjU7ReiycUg97"
```

### **Étape 6 : Traitement du paiement**

#### **Cas 1 : Succès immédiat (T-Money, Moov)**

```
✅ [PayDunya PER] Request Successful
   └─ response_code: "00"

✅ [PayDunya PER] Facture soumise avec succès
   ├─ status: "success"
   ├─ transaction_id: "TFA-TX-37XqPpVCjU7ReiycUg97"
   └─ provider_ref: "565486545315"
```

#### **Cas 2 : En attente (Orange Money Mali, Orange Money Burkina)**

```
✅ [PayDunya PER] Request Successful
   └─ response_code: "00"

⚠️ [PayDunya PER] Facture soumise avec succès
   ├─ status: "pending"
   ├─ transaction_id: "TFA-TX-37XqPpVCjU7ReiycUg97"
   └─ message: "Transaction pending, please check the final status later"
```

### **Étape 7 : Callback PayDunya (IPN)**

```
🔔 [PayDunya Callback PER] ========== CALLBACK REÇU ==========

🔔 [PayDunya Callback PER] Payload complet
   └─ payload: {
        "status": "success",
        "token": "hwTHAS0WvTmTaYT2zDoO",
        "withdraw_mode": "t-money-togo",
        "amount": "5000.00",
        "transaction_id": "TFA-TX-37XqPpVCjU7ReiycUg97",
        "disburse_id": "withdrawal_123456",
        "disburse_tx_id": "CI000111.1430.A11197"
      }

📋 [PayDunya Callback PER] Extraction des données
   ├─ token: "hwTHAS0WvTmTaYT2zDoO"
   ├─ status: "success"
   └─ transaction_id: "TFA-TX-37XqPpVCjU7ReiycUg97"

🎯 [PayDunya Callback PER] Statut normalisé
   ├─ original_status: "success"
   └─ normalized_status: "completed"

💰 [PayDunya Callback PER] Traitement du retrait complété

🔄 [PayDunya Callback PER] Transaction DB démarrée

💳 [PayDunya Callback PER] Wallet trouvé
   ├─ wallet_id: 456
   └─ current_balance: 15000

➖ [PayDunya Callback PER] Débit du wallet en cours

✅ [PayDunya Callback PER] Wallet débité, mise à jour du statut

🎉 [PayDunya Callback PER] Retrait complété avec succès
   ├─ token: "hwTHAS0WvTmTaYT2zDoO"
   ├─ amount: 5000
   ├─ wallet_id: 456
   └─ new_balance: 10000

✅ [PayDunya Callback PER] ========== CALLBACK TRAITÉ AVEC SUCCÈS ==========
```

---

## 🔴 Logs d'erreur courants

### **1. Credentials manquantes**

```
🔴 [PayDunya PER] Credentials manquantes
   ├─ has_master_key: false
   ├─ has_private_key: false
   └─ has_token: false

➡️ Solution : Vérifiez votre fichier .env
```

### **2. Fonds insuffisants**

```
❌ [PayDunya PER] HTTP Request Failed
   ├─ status_code: 200
   ├─ response_code: "4002"
   └─ error_message: "You don't have enough funds. Consider crediting your account"

➡️ Solution : Créditez votre compte PayDunya Business
```

### **3. Callback URL non accessible**

```
❌ [PayDunya PER] HTTP Request Failed
   ├─ response_code: "4002"
   └─ error_message: "the callback is not accessible"

➡️ Solution : Vérifiez que l'URL de callback est accessible depuis Internet
```

### **4. Withdraw mode non supporté**

```
❌ [PayDunya PER] HTTP Request Failed
   ├─ response_code: "1001"
   └─ error_message: "withdraw_mode non pris en charge"

➡️ Solution : Utilisez un des withdraw_mode supportés (voir liste)
```

### **5. Disburse ID déjà utilisé**

```
❌ [PayDunya PER] HTTP Request Failed
   ├─ response_code: "5000"
   └─ error_message: "disburse_id already used"

➡️ Solution : Utilisez un nouveau disburse_id unique
```

### **6. Service en maintenance**

```
❌ [PayDunya PER] HTTP Request Failed
   ├─ response_code: "5000"
   └─ error_message: "Service en maintenance, veuillez réessayer plus tard"

➡️ Solution : Attendez et réessayez plus tard
```

### **7. Token introuvable**

```
🔴 [PayDunya Callback PER] Token introuvable en base
   ├─ token: "hwTHAS0WvTmTaYT2zDoO"
   └─ searched_in: "withdrawal_phone_numbers ou autre table"

➡️ Solution : La facture n'a pas été enregistrée correctement
```

---

## 🛠️ Commandes utiles pour le débogage

### **1. Suivre les logs en temps réel**

```bash
# Tous les logs PayDunya PER
tail -f storage/logs/laravel.log | grep "PayDunya PER"

# Uniquement les erreurs PER
tail -f storage/logs/laravel.log | grep "🔴\\|❌\\|💥" | grep "PER"

# Uniquement les succès PER
tail -f storage/logs/laravel.log | grep "✅\\|🎉" | grep "PER"
```

### **2. Rechercher un token spécifique**

```bash
grep "hwTHAS0WvTmTaYT2zDoO" storage/logs/laravel.log
```

### **3. Compter les retraits complétés aujourd'hui**

```bash
grep "Retrait complété avec succès" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l
```

### **4. Vérifier les callbacks reçus**

```bash
grep "PayDunya Callback PER" storage/logs/laravel.log | grep "CALLBACK REÇU"
```

---

## 📊 Statuts possibles

### **Statuts intermédiaires :**
- `created` : Facture créée mais pas encore soumise
- `pending` : Facture soumise, traitement en cours chez l'opérateur

### **Statuts finaux :**
- `success` / `completed` : Transaction réussie
- `failed` : Transaction échouée

---

## 🔄 Gestion des statuts pending

Selon la documentation PayDunya, si vous recevez un statut `pending` :

1. **Patienter** jusqu'au statut final
2. **Vérifier le statut** avec l'API Check Status :

```php
$status = $paydunyaDisbursementService->checkStatus($disburseInvoice);
```

3. **Interpréter le résultat** :
   - `SUCCESS` → Clore la transaction
   - `PENDING` → Continuer à attendre
   - `FAILED` → Clore la transaction, nouvelle tentative si nécessaire
   - `CREATED` → Recommencer le Submit avec le même token

---

## 📝 Checklist de débogage

1. ✅ Les clés API PER sont configurées dans `.env` ?
2. ✅ Vous avez des fonds suffisants sur votre compte PayDunya Business ?
3. ✅ L'URL de callback est accessible depuis Internet ?
4. ✅ Le numéro de téléphone est au bon format (sans code pays) ?
5. ✅ Le withdraw_mode correspond à l'opérateur du numéro ?
6. ✅ Le disburse_id est unique (si fourni) ?

---

## 🎯 Exemple de log complet (succès)

```
[2025-01-25 14:00:00] 🔵 [PayDunya PER] Début createInvoice amount=5000 withdraw_mode=t-money-togo
[2025-01-25 14:00:00] 🟢 [PayDunya PER] Credentials OK
[2025-01-25 14:00:00] 📤 [PayDunya PER] Création de la facture de décaissement
[2025-01-25 14:00:01] 🌐 [PayDunya PER] POST Request Details
[2025-01-25 14:00:02] 📥 [PayDunya PER] POST Response status_code=200
[2025-01-25 14:00:02] ✅ [PayDunya PER] Request Successful
[2025-01-25 14:00:02] 🎉 [PayDunya PER] Facture créée disburse_invoice=hwTHAS0W
[2025-01-25 14:00:02] 🚀 [PayDunya PER] Début submitInvoice
[2025-01-25 14:00:02] 📤 [PayDunya PER] Soumission de la facture
[2025-01-25 14:00:04] 📥 [PayDunya PER] POST Response status_code=200
[2025-01-25 14:00:04] ✅ [PayDunya PER] Facture soumise status=success
[2025-01-25 14:00:15] 🔔 [PayDunya Callback PER] ========== CALLBACK REÇU ==========
[2025-01-25 14:00:15] 📋 [PayDunya Callback PER] Extraction des données
[2025-01-25 14:00:15] 💰 [PayDunya Callback PER] Traitement du retrait
[2025-01-25 14:00:15] 💳 [PayDunya Callback PER] Wallet trouvé
[2025-01-25 14:00:15] ➖ [PayDunya Callback PER] Débit du wallet
[2025-01-25 14:00:16] 🎉 [PayDunya Callback PER] Retrait complété new_balance=10000
[2025-01-25 14:00:16] ✅ [PayDunya Callback PER] ========== CALLBACK TRAITÉ AVEC SUCCÈS ==========
```

---

## 💡 Conseils

1. **En développement** : Activez `APP_DEBUG=true` pour voir les logs `debug` avec les payloads complets
2. **En production** : Gardez `APP_DEBUG=false`, les logs `info` suffisent
3. **Utilisez les emojis** : Ils facilitent la lecture visuelle des logs
4. **Surveillez les "pending"** : Mettez en place un système de vérification automatique pour les transactions pending
5. **Testez les montants** : Commencez avec des petits montants en test

---

## 🔗 Liens utiles

- Documentation PayDunya PER : https://paydunya.com/developers
- Support PayDunya : support@paydunya.com
- Fichier de config : `config/services.php` → section `paydunya.disburse`
