# Guide d'implémentation PayDunya PSR (Paiement Sans Redirection) - Mobile Flutter

Ce guide explique comment intégrer le PSR PayDunya dans l'application mobile Flutter.

## 📍 Vue d'ensemble

Le PSR (Paiement Sans Redirection) permet aux utilisateurs de payer directement dans l'app mobile via une WebView, sans être redirigés vers un navigateur externe.

### **Flow du paiement PSR**

1. **User** → Clique sur "Recharger le wallet"
2. **App** → Appelle `/api/paydunya/psr/token` pour obtenir un token
3. **App** → Affiche une WebView avec le formulaire de paiement PayDunya
4. **User** → Entre ses informations de paiement dans la WebView
5. **PayDunya** → Traite le paiement
6. **App** → Reçoit le callback `onTerminate` avec le statut
7. **Backend** → Reçoit le callback IPN de PayDunya et crédite le wallet
8. **App** → Met à jour l'UI

---

## 🔧 Backend (API Laravel) - Déjà implémenté ✅

### **Endpoint PSR**

```http
POST /api/paydunya/psr/token
```

**Request Body:**
```json
{
  "user_id": 123,
  "wallet_id": "abc123",
  "amount": 5000,
  "description": "Recharge de wallet"
}
```

**Response (Success):**
```json
{
  "success": true,
  "token": "hwTHAS0WvTmTaYT2zDoO"
}
```

**Response (Test Mode):**
```json
{
  "success": true,
  "mode": "test",
  "token": "hwTHAS0WvTmTaYT2zDoO"
}
```

---

## 📱 Frontend (Flutter Mobile) - À implémenter

### **1. Dépendances à ajouter**

Dans `pubspec.yaml` :

```yaml
dependencies:
  flutter_inappwebview: ^5.8.0  # Pour afficher la WebView
  http: ^1.1.0  # Pour les requêtes HTTP
```

### **2. Modèle de données**

Créer `lib/models/paydunya_psr_response.dart` :

```dart
class PaydunyaPSRResponse {
  final bool success;
  final String? token;
  final String? mode;

  PaydunyaPSRResponse({
    required this.success,
    this.token,
    this.mode,
  });

  factory PaydunyaPSRResponse.fromJson(Map<String, dynamic> json) {
    return PaydunyaPSRResponse(
      success: json['success'] ?? false,
      token: json['token'],
      mode: json['mode'],
    );
  }
}
```

### **3. Service API**

Créer `lib/services/paydunya_psr_service.dart` :

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/paydunya_psr_response.dart';

class PaydunyaPSRService {
  final String baseUrl;

  PaydunyaPSRService({required this.baseUrl});

  Future<PaydunyaPSRResponse> getPSRToken({
    required int userId,
    required String walletId,
    required double amount,
    String? description,
  }) async {
    try {
      final url = Uri.parse('$baseUrl/api/paydunya/psr/token');

      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'user_id': userId,
          'wallet_id': walletId,
          'amount': amount,
          'description': description ?? 'Recharge de wallet',
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return PaydunyaPSRResponse.fromJson(data);
      } else {
        throw Exception('Erreur lors de la récupération du token: ${response.body}');
      }
    } catch (e) {
      throw Exception('Erreur réseau: $e');
    }
  }
}
```

### **4. Widget WebView pour le paiement**

Créer `lib/widgets/paydunya_psr_webview.dart` :

```dart
import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';

class PaydunyaPSRWebView extends StatefulWidget {
  final String token;
  final Function(String status) onPaymentComplete;
  final VoidCallback? onClose;

  const PaydunyaPSRWebView({
    Key? key,
    required this.token,
    required this.onPaymentComplete,
    this.onClose,
  }) : super(key: key);

  @override
  State<PaydunyaPSRWebView> createState() => _PaydunyaPSRWebViewState();
}

class _PaydunyaPSRWebViewState extends State<PaydunyaPSRWebView> {
  late InAppWebViewController _webViewController;
  bool _isLoading = true;
  String? _paymentStatus;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Paiement PayDunya'),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () {
            _handleClose();
          },
        ),
      ),
      body: Stack(
        children: [
          InAppWebView(
            initialData: InAppWebViewInitialData(
              data: _buildHTML(),
              encoding: 'utf-8',
              mimeType: 'text/html',
            ),
            initialOptions: InAppWebViewGroupOptions(
              crossPlatform: InAppWebViewOptions(
                javaScriptEnabled: true,
                javaScriptCanOpenWindowsAutomatically: true,
              ),
            ),
            onWebViewCreated: (controller) {
              _webViewController = controller;

              // Ajouter un handler JavaScript pour recevoir les callbacks PayDunya
              _webViewController.addJavaScriptHandler(
                handlerName: 'paymentComplete',
                callback: (args) {
                  if (args.isNotEmpty) {
                    final status = args[0] as String;
                    _handlePaymentComplete(status);
                  }
                },
              );
            },
            onLoadStart: (controller, url) {
              setState(() {
                _isLoading = true;
              });
            },
            onLoadStop: (controller, url) {
              setState(() {
                _isLoading = false;
              });
            },
          ),
          if (_isLoading)
            const Center(
              child: CircularProgressIndicator(),
            ),
        ],
      ),
    );
  }

  String _buildHTML() {
    return '''
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement PayDunya</title>
    <link rel="stylesheet" type="text/css" href="https://paydunya.com/assets/psr/css/psr.paydunya.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .pay {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .pay:hover {
            background-color: #45a049;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Finaliser le paiement</h2>
        <button class="pay" onclick="payWithPaydunya(this)">
            Payer maintenant
        </button>
    </div>

    <script src="https://code.jquery.com/jquery.min.js"></script>
    <script src="https://paydunya.com/assets/psr/js/psr.paydunya.min.js"></script>
    <script>
        function payWithPaydunya(btn) {
            PayDunya.setup({
                selector: \$(btn),
                url: "data:application/json,{\\"success\\":true,\\"token\\":\\"${widget.token}\\"}",
                method: "GET",
                displayMode: PayDunya.DISPLAY_IN_POPUP,
                beforeRequest: function() {
                    console.log("About to get token");
                },
                onSuccess: function(token) {
                    console.log("Token received: " + token);
                },
                onTerminate: function(ref, token, status) {
                    console.log("Payment terminated - Status: " + status);
                    // Envoyer le statut à Flutter
                    if (window.flutter_inappwebview) {
                        window.flutter_inappwebview.callHandler('paymentComplete', status);
                    }
                },
                onError: function (error) {
                    console.error("Error: ", error.toString());
                    if (window.flutter_inappwebview) {
                        window.flutter_inappwebview.callHandler('paymentComplete', 'FAILED');
                    }
                },
                onUnsuccessfulResponse: function (jsonResponse) {
                    console.log("Unsuccessful response: " + jsonResponse);
                },
                onClose: function() {
                    console.log("Payment window closed");
                }
            }).requestToken();
        }

        // Auto-déclencher le paiement après 1 seconde
        setTimeout(function() {
            payWithPaydunya(document.querySelector('.pay'));
        }, 1000);
    </script>
</body>
</html>
    ''';
  }

  void _handlePaymentComplete(String status) {
    setState(() {
      _paymentStatus = status;
    });

    // Fermer la WebView et notifier le parent
    widget.onPaymentComplete(status);
    Navigator.of(context).pop();
  }

  void _handleClose() {
    if (widget.onClose != null) {
      widget.onClose!();
    }
    Navigator.of(context).pop();
  }
}
```

### **5. Écran de recharge du wallet**

Créer `lib/screens/wallet_recharge_screen.dart` :

```dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../services/paydunya_psr_service.dart';
import '../widgets/paydunya_psr_webview.dart';

class WalletRechargeScreen extends StatefulWidget {
  const WalletRechargeScreen({Key? key}) : super(key: key);

  @override
  State<WalletRechargeScreen> createState() => _WalletRechargeScreenState();
}

class _WalletRechargeScreenState extends State<WalletRechargeScreen> {
  final TextEditingController _amountController = TextEditingController();
  final PaydunyaPSRService _psrService = PaydunyaPSRService(
    baseUrl: 'https://your-api-url.com',
  );
  bool _isLoading = false;

  Future<void> _initiatePayment() async {
    final amount = double.tryParse(_amountController.text);

    if (amount == null || amount < 100) {
      Get.snackbar(
        'Erreur',
        'Le montant minimum est de 100 FCFA',
        snackPosition: SnackPosition.BOTTOM,
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      // Récupérer l'user_id et wallet_id depuis votre state management (GetX, Provider, etc.)
      final userId = 123;  // À remplacer
      final walletId = 'abc123';  // À remplacer

      final response = await _psrService.getPSRToken(
        userId: userId,
        walletId: walletId,
        amount: amount,
      );

      if (response.success && response.token != null) {
        // Ouvrir la WebView PayDunya
        _openPaymentWebView(response.token!);
      } else {
        Get.snackbar(
          'Erreur',
          'Impossible de générer le token de paiement',
          snackPosition: SnackPosition.BOTTOM,
        );
      }
    } catch (e) {
      Get.snackbar(
        'Erreur',
        'Une erreur est survenue: $e',
        snackPosition: SnackPosition.BOTTOM,
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _openPaymentWebView(String token) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => PaydunyaPSRWebView(
          token: token,
          onPaymentComplete: (status) {
            _handlePaymentResult(status);
          },
          onClose: () {
            print('Payment window closed');
          },
        ),
      ),
    );
  }

  void _handlePaymentResult(String status) {
    String message;
    Color backgroundColor;

    switch (status.toUpperCase()) {
      case 'COMPLETED':
        message = 'Paiement réussi! Votre wallet a été crédité.';
        backgroundColor = Colors.green;
        // Rafraîchir le solde du wallet ici
        break;
      case 'PENDING':
        message = 'Paiement en cours de traitement...';
        backgroundColor = Colors.orange;
        break;
      case 'FAILED':
        message = 'Le paiement a échoué. Veuillez réessayer.';
        backgroundColor = Colors.red;
        break;
      case 'CANCELLED':
        message = 'Paiement annulé.';
        backgroundColor = Colors.grey;
        break;
      default:
        message = 'Statut du paiement: $status';
        backgroundColor = Colors.blue;
    }

    Get.snackbar(
      'Statut du paiement',
      message,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: backgroundColor,
      colorText: Colors.white,
      duration: const Duration(seconds: 5),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Recharger le wallet'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Montant à recharger (FCFA)',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _amountController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                hintText: '5000',
                prefixText: 'FCFA ',
              ),
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : _initiatePayment,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 15),
              ),
              child: _isLoading
                  ? const CircularProgressIndicator(color: Colors.white)
                  : const Text(
                      'Recharger avec PayDunya',
                      style: TextStyle(fontSize: 16),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }
}
```

---

## 🔄 Statuts possibles

Après le paiement, le callback `onTerminate` retourne un de ces statuts :

| Statut | Description | Action recommandée |
|--------|-------------|-------------------|
| **COMPLETED** | Paiement réussi | Rafraîchir le solde du wallet |
| **PENDING** | En cours de traitement | Attendre la confirmation |
| **FAILED** | Paiement échoué | Permettre de réessayer |
| **CANCELLED** | Paiement annulé par l'utilisateur | Retour à l'écran précédent |

---

## 🎯 Points importants

1. **WebView** : Utiliser `flutter_inappwebview` et non `webview_flutter` pour une meilleure intégration JavaScript
2. **Token unique** : Chaque paiement nécessite un nouveau token
3. **Callback backend** : Le backend reçoit aussi un callback IPN de PayDunya pour créditer le wallet
4. **Synchronisation** : L'app doit rafraîchir le solde après un paiement `COMPLETED`
5. **Mode Test** : PayDunya retourne `mode: "test"` en mode test uniquement

---

## 🧪 Test

### **Mode Test**
1. Configurer `PAYDUNYA_CHECKOUT_MODE=test` dans le `.env`
2. Utiliser les numéros de test PayDunya
3. Le token retourné aura `mode: "test"`

### **Mode Production**
1. Configurer `PAYDUNYA_CHECKOUT_MODE=live` dans le `.env`
2. Le champ `mode` ne sera pas présent dans la réponse

---

## 📝 Checklist d'implémentation

- [ ] ✅ Backend : Endpoint `/api/paydunya/psr/token` créé
- [ ] ✅ Backend : Callback PayDunya déjà en place
- [ ] ⬜ Flutter : Ajouter `flutter_inappwebview` dans `pubspec.yaml`
- [ ] ⬜ Flutter : Créer `PaydunyaPSRService`
- [ ] ⬜ Flutter : Créer `PaydunyaPSRWebView`
- [ ] ⬜ Flutter : Intégrer dans l'écran de recharge
- [ ] ⬜ Flutter : Tester en mode test
- [ ] ⬜ Flutter : Déployer en production

---

## 🔗 Ressources

- [Documentation PayDunya PSR](https://paydunya.com/developers/api-psr)
- [flutter_inappwebview](https://pub.dev/packages/flutter_inappwebview)
- Backend endpoint: `POST /api/paydunya/psr/token`
- Callback backend: `POST /api/paydunya/payment/callback` (déjà en place)
