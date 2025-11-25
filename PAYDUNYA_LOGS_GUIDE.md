# Guide des Logs PayDunya Checkout

Ce guide vous aide à suivre et déboguer le processus de paiement PayDunya Checkout dans les logs.

## 📍 Où trouver les logs ?

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Filtrer uniquement les logs PayDunya Checkout
tail -f storage/logs/laravel.log | grep "PayDunya Checkout"

# Filtrer uniquement les logs du callback
tail -f storage/logs/laravel.log | grep "PayDunya Callback"
```

---

## 🎯 Flow complet des logs

### **Étape 1 : Tentative CinetPay (si activé)**

```
CinetPay request/response logs...
Si échec → Passage à PayDunya
```

### **Étape 2 : Création de la facture PayDunya**

```
🔵 [PayDunya Checkout] Début createInvoice
   ├─ amount: 5000
   ├─ items_count: 1
   └─ has_options: true

🟢 [PayDunya Checkout] Credentials OK, préparation du payload
   ├─ store_name: "CHARM"
   └─ mode: "live"

📦 [PayDunya Checkout] Items ajoutés
   └─ items_count: 1

📝 [PayDunya Checkout] Custom data ajoutées
   └─ keys: ["user_id", "wallet_id", "transaction_id"]

📤 [PayDunya Checkout] Envoi de la requête à l'API
   ├─ endpoint: "/checkout-invoice/create"
   └─ callback_url: "https://votreapi.com/api/paydunya/checkout/callback"
```

### **Étape 3 : Requête HTTP vers PayDunya**

```
🌐 [PayDunya Checkout] POST Request Details
   ├─ url: "https://app.paydunya.com/api/v1/checkout-invoice/create"
   ├─ endpoint: "/checkout-invoice/create"
   ├─ has_invoice: true
   ├─ has_store: true
   ├─ has_actions: true
   └─ headers_present: ["Content-Type", "PAYDUNYA-MASTER-KEY", ...]

📋 [PayDunya Checkout] Payload complet
   └─ payload: {...}  // Détails complets en mode debug
```

### **Étape 4 : Réponse de l'API PayDunya**

```
📥 [PayDunya Checkout] POST Response Received
   ├─ status_code: 200
   ├─ success: true
   └─ response_code: "00"

📋 [PayDunya Checkout] Response body complet
   └─ body: {...}  // Détails complets en mode debug

✅ [PayDunya Checkout] Request Successful
   └─ response_code: "00"

✅ [PayDunya Checkout] Réponse API reçue avec succès

🎉 [PayDunya Checkout] Facture créée avec succès
   ├─ token: "test_abc123xyz"
   └─ payment_url: "https://app.paydunya.com/checkout/test_abc123xyz"
```

### **Étape 5 : Enregistrement en base de données**

```
[PayDunya] PaydunyaPaymentRequest créé
   ├─ user_id: 123
   ├─ wallet_id: 456
   ├─ amount: 5000
   ├─ reference_number: "test_abc123xyz"
   ├─ status: "pending"
   └─ payment_url: "https://..."
```

### **Étape 6 : L'utilisateur paie sur PayDunya**

*L'utilisateur est redirigé vers la page PayDunya et effectue le paiement*

---

### **Étape 7 : Callback PayDunya (IPN)**

```
🔔 [PayDunya Callback] ========== CALLBACK REÇU ==========

🔔 [PayDunya Callback] Payload complet
   └─ payload: {...}

🔐 [PayDunya Callback] Vérification du hash
   ├─ has_received_hash: true
   └─ master_key_configured: true

✅ [PayDunya Callback] Hash valide

📋 [PayDunya Callback] Extraction des données
   ├─ token: "test_abc123xyz"
   ├─ status: "completed"
   └─ has_invoice_data: true

🔍 [PayDunya Callback] Recherche de la demande de paiement
   └─ token: "test_abc123xyz"

✅ [PayDunya Callback] Demande de paiement trouvée
   ├─ payment_request_id: 789
   ├─ user_id: 123
   ├─ amount: 5000
   └─ current_status: "pending"

🎯 [PayDunya Callback] Statut normalisé
   ├─ original_status: "completed"
   ├─ normalized_status: "completed"
   └─ needs_processing: true

💰 [PayDunya Callback] Début du traitement du paiement complété

🔄 [PayDunya Callback] Transaction DB démarrée

💳 [PayDunya Callback] Wallet trouvé
   ├─ wallet_id: 456
   └─ current_balance: 10000

➕ [PayDunya Callback] Crédit du wallet en cours

✅ [PayDunya Callback] Wallet crédité, mise à jour du statut

🎉 [PayDunya Callback] Transaction complétée avec succès
   ├─ token: "test_abc123xyz"
   ├─ amount: 5000
   ├─ wallet_id: 456
   └─ new_balance: 15000

✅ [PayDunya Callback] ========== CALLBACK TRAITÉ AVEC SUCCÈS ==========
```

---

## 🔴 Logs d'erreur courants

### **1. Credentials manquantes**

```
🔴 [PayDunya Checkout] Credentials manquantes
   ├─ has_master_key: false
   ├─ has_public_key: false
   ├─ has_private_key: false
   └─ has_token: false

➡️ Solution : Vérifiez votre fichier .env
```

### **2. Échec de la requête HTTP**

```
❌ [PayDunya Checkout] HTTP Request Failed
   ├─ status_code: 400
   ├─ error_message: "Invalid request"
   └─ full_response: {...}

➡️ Solution : Vérifiez le payload envoyé
```

### **3. Token ou URL manquant dans la réponse**

```
🔴 [PayDunya Checkout] Token ou URL manquant dans la réponse
   ├─ has_token: false
   ├─ has_response_url: false
   └─ data_keys: ["error", "message"]

➡️ Solution : Vérifiez vos clés API et le mode (test/live)
```

### **4. Hash invalide (callback)**

```
🔴 [PayDunya Callback] Hash invalide - Possible tentative frauduleuse
   ├─ received_hash: "abc123..."
   └─ expected_hash: "xyz789..."

➡️ Solution : Vérifiez que PAYDUNYA_CHECKOUT_MASTER_KEY est correct
```

### **5. Token introuvable (callback)**

```
🔴 [PayDunya Callback] Demande de paiement introuvable en base
   ├─ token: "test_abc123xyz"
   └─ searched_in: "paydunya_payment_requests.reference_number"

➡️ Solution : La facture n'a pas été enregistrée correctement
```

### **6. Wallet introuvable**

```
🔴 [PayDunya Callback] Wallet introuvable
   ├─ payment_request_id: 789
   └─ wallet_id: 456

➡️ Solution : Le wallet a été supprimé ou l'ID est incorrect
```

### **7. Exception lors du traitement**

```
💥 [PayDunya Callback] Exception lors du traitement
   ├─ token: "test_abc123xyz"
   ├─ exception_type: "Exception"
   ├─ exception_message: "Impossible de créditer le wallet"
   └─ trace: "..."

➡️ Solution : Vérifiez la trace pour identifier le problème
```

---

## 🛠️ Commandes utiles pour le débogage

### **1. Suivre les logs en temps réel**

```bash
# Tous les logs PayDunya
tail -f storage/logs/laravel.log | grep -E "PayDunya Checkout|PayDunya Callback"

# Uniquement les erreurs
tail -f storage/logs/laravel.log | grep "🔴\|❌\|💥"

# Uniquement les succès
tail -f storage/logs/laravel.log | grep "✅\|🎉"
```

### **2. Rechercher un token spécifique**

```bash
grep "test_abc123xyz" storage/logs/laravel.log
```

### **3. Compter les paiements complétés aujourd'hui**

```bash
grep "Transaction complétée avec succès" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l
```

### **4. Vérifier les callbacks reçus**

```bash
grep "CALLBACK REÇU" storage/logs/laravel.log
```

---

## 📊 Indicateurs de santé

### ✅ **Tout fonctionne bien si vous voyez :**

```
🔵 Début createInvoice
🟢 Credentials OK
✅ Request Successful
🎉 Facture créée avec succès
🔔 CALLBACK REÇU
🎉 Transaction complétée avec succès
```

### ⚠️ **Problème potentiel si vous voyez :**

```
⚠️ Request Completed but not successful
   └─ Vérifiez le response_code et le message

ℹ️ Mise à jour du statut seulement (pas de crédit)
   └─ Le callback n'a pas le statut "completed"
```

### 🔴 **Problème critique si vous voyez :**

```
🔴 Credentials manquantes
❌ HTTP Request Failed
💥 Exception during POST request
🔴 Hash invalide
🔴 Wallet introuvable
```

---

## 📝 Checklist de débogage

1. ✅ Les clés API sont configurées dans `.env` ?
2. ✅ Le mode est correct (test/live) ?
3. ✅ L'URL de callback est accessible depuis Internet ?
4. ✅ Le wallet de l'utilisateur existe ?
5. ✅ La table `paydunya_payment_requests` contient bien la requête ?
6. ✅ Le hash SHA-512 correspond bien au master_key ?

---

## 🎯 Exemple de log complet (succès)

```
[2025-01-25 10:30:00] 🔵 [PayDunya Checkout] Début createInvoice amount=5000
[2025-01-25 10:30:00] 🟢 [PayDunya Checkout] Credentials OK
[2025-01-25 10:30:00] 📦 [PayDunya Checkout] Items ajoutés items_count=1
[2025-01-25 10:30:00] 📤 [PayDunya Checkout] Envoi de la requête
[2025-01-25 10:30:01] 🌐 [PayDunya Checkout] POST Request Details
[2025-01-25 10:30:02] 📥 [PayDunya Checkout] POST Response status_code=200
[2025-01-25 10:30:02] ✅ [PayDunya Checkout] Request Successful
[2025-01-25 10:30:02] 🎉 [PayDunya Checkout] Facture créée token=test_abc123
[2025-01-25 10:35:15] 🔔 [PayDunya Callback] ========== CALLBACK REÇU ==========
[2025-01-25 10:35:15] ✅ [PayDunya Callback] Hash valide
[2025-01-25 10:35:15] ✅ [PayDunya Callback] Demande trouvée payment_request_id=789
[2025-01-25 10:35:15] 💰 [PayDunya Callback] Début traitement paiement
[2025-01-25 10:35:15] 💳 [PayDunya Callback] Wallet trouvé wallet_id=456
[2025-01-25 10:35:15] ➕ [PayDunya Callback] Crédit du wallet
[2025-01-25 10:35:16] 🎉 [PayDunya Callback] Transaction complétée new_balance=15000
[2025-01-25 10:35:16] ✅ [PayDunya Callback] ========== CALLBACK TRAITÉ AVEC SUCCÈS ==========
```

---

## 💡 Conseils

1. **En développement** : Activez `APP_DEBUG=true` pour voir les logs `debug` avec les payloads complets
2. **En production** : Gardez `APP_DEBUG=false`, les logs `info` suffisent
3. **Utilisez les emojis** : Ils facilitent la lecture visuelle des logs
4. **Créez des alertes** : Surveillez les logs `🔴` et `💥` pour être notifié des erreurs

---

## 🔗 Liens utiles

- Documentation PayDunya : https://paydunya.com/developers
- Support PayDunya : support@paydunya.com
- Fichier de config : `config/services.php` → section `paydunya.checkout`
