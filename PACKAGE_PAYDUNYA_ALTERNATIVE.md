# Alternative : Utiliser le package officiel PayDunya

Si vous préférez utiliser le package officiel PayDunya au lieu de notre implémentation personnalisée, voici comment procéder.

## Installation

```bash
composer require paydunya/paydunya
```

## Service wrapper avec le package officiel

Créez le fichier `app/Services/PaydunyaCheckoutPackageService.php` :

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Paydunya\Setup;
use Paydunya\Checkout\Store;
use Paydunya\Checkout\CheckoutInvoice;

class PaydunyaCheckoutPackageService
{
    public function __construct()
    {
        // Configuration PayDunya
        $config = config('services.paydunya.checkout', []);

        Setup::setMasterKey($config['master_key'] ?? '');
        Setup::setPublicKey($config['public_key'] ?? '');
        Setup::setPrivateKey($config['private_key'] ?? '');
        Setup::setToken($config['token'] ?? '');
        Setup::setMode($config['mode'] ?? 'live');

        // Configuration du store
        Store::setName($config['store_name'] ?? config('app.name'));
        Store::setTagline($config['store_tagline'] ?? '');
        Store::setPhoneNumber($config['store_phone'] ?? '');
        Store::setPostalAddress($config['store_postal_address'] ?? '');
        Store::setWebsiteUrl($config['store_website_url'] ?? config('app.url'));
        Store::setLogoUrl($config['store_logo_url'] ?? '');

        // URLs de callback
        if (!empty($config['callback_url'])) {
            Store::setCallbackUrl($config['callback_url']);
        }
        if (!empty($config['return_url'])) {
            Store::setReturnUrl($config['return_url']);
        }
        if (!empty($config['cancel_url'])) {
            Store::setCancelUrl($config['cancel_url']);
        }
    }

    public function createInvoice(float $amount, array $items = [], array $options = []): array
    {
        try {
            $invoice = new CheckoutInvoice();

            // Ajouter les items
            foreach ($items as $item) {
                $invoice->addItem(
                    $item['name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price'],
                    $item['description'] ?? ''
                );
            }

            // Ajouter les taxes si présentes
            if (isset($options['taxes'])) {
                foreach ($options['taxes'] as $tax) {
                    $invoice->addTax($tax['name'], $tax['amount']);
                }
            }

            // Définir le montant total
            $invoice->setTotalAmount((int) $amount);

            // Description
            if (isset($options['description'])) {
                $invoice->setDescription($options['description']);
            }

            // Custom data
            if (isset($options['custom_data'])) {
                foreach ($options['custom_data'] as $key => $value) {
                    $invoice->addCustomData($key, $value);
                }
            }

            // URLs spécifiques pour cette facture
            if (isset($options['callback_url'])) {
                $invoice->setCallbackUrl($options['callback_url']);
            }
            if (isset($options['return_url'])) {
                $invoice->setReturnUrl($options['return_url']);
            }
            if (isset($options['cancel_url'])) {
                $invoice->setCancelUrl($options['cancel_url']);
            }

            // Restriction des canaux de paiement
            if (isset($options['channels'])) {
                foreach ($options['channels'] as $channel) {
                    $invoice->addChannel($channel);
                }
            }

            Log::info('Création facture PayDunya avec package officiel', [
                'amount' => $amount,
                'items_count' => count($items),
            ]);

            // Créer la facture
            if ($invoice->create()) {
                $token = $invoice->token;
                $invoiceUrl = $invoice->getInvoiceUrl();

                Log::info('Facture PayDunya créée avec succès', [
                    'token' => $token,
                    'url' => $invoiceUrl,
                ]);

                return [
                    'success' => true,
                    'message' => 'Facture PayDunya créée avec succès',
                    'data' => [
                        'token' => $token,
                        'invoice_url' => $invoiceUrl,
                        'payment_url' => $invoiceUrl,
                        'raw' => [
                            'token' => $token,
                            'response_url' => $invoiceUrl,
                        ],
                    ],
                ];
            } else {
                Log::error('Échec création facture PayDunya', [
                    'response_text' => $invoice->response_text ?? 'Erreur inconnue',
                    'response_code' => $invoice->response_code ?? null,
                ]);

                return [
                    'success' => false,
                    'message' => $invoice->response_text ?? 'Erreur lors de la création de la facture',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de la création facture PayDunya', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la communication avec PayDunya: ' . $e->getMessage(),
            ];
        }
    }

    public function confirmInvoice(string $token): array
    {
        try {
            $invoice = new CheckoutInvoice();

            Log::info('Vérification statut facture PayDunya', ['token' => $token]);

            if ($invoice->confirm($token)) {
                $status = $invoice->getStatus();

                Log::info('Statut facture PayDunya récupéré', [
                    'token' => $token,
                    'status' => $status,
                ]);

                return [
                    'success' => true,
                    'message' => 'Statut récupéré avec succès',
                    'data' => [
                        'status' => $status,
                        'customer' => [
                            'name' => $invoice->getCustomerInfo('name'),
                            'email' => $invoice->getCustomerInfo('email'),
                            'phone' => $invoice->getCustomerInfo('phone'),
                        ],
                        'receipt_url' => $invoice->getReceiptUrl(),
                        'total_amount' => $invoice->getTotalAmount(),
                        'raw' => [
                            'status' => $status,
                        ],
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $invoice->response_text ?? 'Erreur lors de la vérification du statut',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de la vérification statut PayDunya', [
                'token' => $token,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la communication avec PayDunya: ' . $e->getMessage(),
            ];
        }
    }
}
```

## Modification du WalletAPIController

Remplacez dans le constructeur :

```php
// Au lieu de
private PaydunyaCheckoutService $paydunyaCheckoutService;

public function __construct(
    // ...
    PaydunyaCheckoutService $paydunyaCheckoutService,
    // ...
)

// Utilisez
private PaydunyaCheckoutPackageService $paydunyaCheckoutService;

public function __construct(
    // ...
    PaydunyaCheckoutPackageService $paydunyaCheckoutService,
    // ...
)
```

Et mettez à jour l'import :
```php
use App\Services\PaydunyaCheckoutPackageService;
```

## Avantages de cette approche

✅ Utilise le package officiel PayDunya
✅ Garde la même interface que notre service personnalisé
✅ Compatible avec le reste du code (pas besoin de changer WalletAPIController)
✅ Bénéficie des mises à jour du package officiel

## Inconvénients

❌ Dépendance externe supplémentaire
❌ Moins de contrôle sur les requêtes HTTP
❌ Variables globales (Setup, Store) pas très Laravel-friendly

## Conclusion

Les deux approches fonctionnent parfaitement. Le choix dépend de vos préférences :

- **Package officiel** : Si vous voulez suivre les mises à jour PayDunya automatiquement
- **Service personnalisé** : Si vous voulez un contrôle total et une intégration Laravel optimale

**Recommandation** : Gardez le service personnalisé actuel (`PaydunyaCheckoutService`) sauf si vous rencontrez des problèmes spécifiques que le package officiel résoudrait.
