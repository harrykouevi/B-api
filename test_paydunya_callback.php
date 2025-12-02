<?php

/**
 * Script de test pour générer un hash PayDunya valide
 * et tester les callbacks en local
 */

// Remplacez par votre MASTER_KEY depuis .env
$masterKey = 'VwXathK3-903l-3YSB-GX2G-D3uMQsMIo2No';

// Calcul du hash SHA-512
$hash = hash('sha512', $masterKey);

echo "=== Test PayDunya Callback ===\n\n";
echo "Master Key: $masterKey\n";
echo "Hash SHA-512: $hash\n\n";

// ============================================
// TEST 1: Checkout Callback - SUCCÈS
// ============================================

$checkoutSuccessPayload = [
    'data' => [
        'hash' => $hash,
        'invoice' => [
            'token' => 'test_token_' . time(),
            'items' => [
                'item_0' => [
                    'name' => 'Recharge de wallet',
                    'quantity' => 1,
                    'unit_price' => '1000',
                    'total_price' => '1000',
                    'description' => 'Recharge de wallet Igris'
                ]
            ],
            'taxes' => [],
            'total_amount' => 1000,
            'status' => 'completed',
            'customer' => [
                'name' => 'Test User',
                'phone' => '+22890123456',
                'email' => 'test@example.com'
            ],
            'actions' => [
                'cancel_url' => 'https://votreapp.com/payment/cancel',
                'callback_url' => 'http://localhost:8000/api/paydunya/payment/callback',
                'return_url' => 'https://votreapp.com/payment/success'
            ],
            'custom_data' => [
                'user_id' => '1',
                'wallet_id' => '1',
                'amount' => '1000',
                'description' => 'Recharge de wallet test'
            ]
        ]
    ]
];

echo "============================================\n";
echo "TEST 1: Checkout Callback - SUCCÈS\n";
echo "============================================\n";
echo "URL: POST http://localhost:8000/api/paydunya/payment/callback\n\n";
echo "Payload JSON:\n";
echo json_encode($checkoutSuccessPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================
// TEST 2: Checkout Callback - ÉCHEC
// ============================================

$checkoutFailedPayload = $checkoutSuccessPayload;
$checkoutFailedPayload['data']['invoice']['status'] = 'failed';
$checkoutFailedPayload['data']['invoice']['token'] = 'test_token_failed_' . time();

echo "============================================\n";
echo "TEST 2: Checkout Callback - ÉCHEC\n";
echo "============================================\n";
echo "URL: POST http://localhost:8000/api/paydunya/payment/callback\n\n";
echo "Payload JSON:\n";
echo json_encode($checkoutFailedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================
// TEST 3: Disburse Callback - SUCCÈS
// ============================================

$disburseSuccessPayload = [
    'status' => 'success',
    'token' => 'test_disburse_token_' . time(),
    'withdraw_mode' => 't-money-togo',
    'amount' => '5000.00',
    'transaction_id' => 'TFA-TX-' . substr(md5(time()), 0, 20),
    'disburse_id' => 'withdrawal_' . time(),
    'disburse_tx_id' => 'CI000111.1430.A' . rand(10000, 99999),
    'provider_ref' => (string)rand(100000000000, 999999999999),
    'timestamp' => date('Y-m-d\TH:i:s\Z')
];

echo "============================================\n";
echo "TEST 3: Disburse Callback - SUCCÈS\n";
echo "============================================\n";
echo "URL: POST http://localhost:8000/api/paydunya/disburse/callback\n\n";
echo "Payload JSON:\n";
echo json_encode($disburseSuccessPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// ============================================
// COMMANDES CURL
// ============================================

echo "============================================\n";
echo "COMMANDES CURL POUR TESTER\n";
echo "============================================\n\n";

echo "# Test 1: Checkout Succès\n";
echo "curl -X POST http://localhost:8000/api/paydunya/payment/callback \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'Accept: application/json' \\\n";
echo "  -d '" . json_encode($checkoutSuccessPayload) . "'\n\n";

echo "# Test 2: Checkout Échec\n";
echo "curl -X POST http://localhost:8000/api/paydunya/payment/callback \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'Accept: application/json' \\\n";
echo "  -d '" . json_encode($checkoutFailedPayload) . "'\n\n";

echo "# Test 3: Disburse Succès\n";
echo "curl -X POST http://localhost:8000/api/paydunya/disburse/callback \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'Accept: application/json' \\\n";
echo "  -d '" . json_encode($disburseSuccessPayload) . "'\n\n";

// ============================================
// SAUVEGARDER LES PAYLOADS DANS DES FICHIERS
// ============================================

file_put_contents(__DIR__ . '/checkout_success_payload.json', json_encode($checkoutSuccessPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents(__DIR__ . '/checkout_failed_payload.json', json_encode($checkoutFailedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents(__DIR__ . '/disburse_success_payload.json', json_encode($disburseSuccessPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "============================================\n";
echo "Fichiers JSON créés:\n";
echo "- checkout_success_payload.json\n";
echo "- checkout_failed_payload.json\n";
echo "- disburse_success_payload.json\n";
echo "============================================\n";
