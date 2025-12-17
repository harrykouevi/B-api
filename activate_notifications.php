<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Activer les notifications
    DB::table('app_settings')
        ->where('key', 'enable_notifications')
        ->update(['value' => '1']);

    echo "✅ Notifications activées avec succès!\n";

    // Vérifier
    $setting = DB::table('app_settings')
        ->where('key', 'enable_notifications')
        ->first();

    if ($setting) {
        echo "   enable_notifications = " . $setting->value . "\n";
    } else {
        echo "⚠️  Setting 'enable_notifications' n'existe pas dans la base!\n";
        echo "   Création du setting...\n";

        DB::table('app_settings')->insert([
            'key' => 'enable_notifications',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Setting créé avec succès!\n";
    }

    // Vérifier le fichier de credentials
    $credentialsPath = storage_path('app/fcm/firebase_credentials.json');
    if (file_exists($credentialsPath)) {
        echo "✅ Fichier Firebase credentials trouvé: $credentialsPath\n";
        $credentials = json_decode(file_get_contents($credentialsPath), true);
        if ($credentials && isset($credentials['project_id'])) {
            echo "   Project ID: " . $credentials['project_id'] . "\n";
        }
    } else {
        echo "❌ Fichier Firebase credentials MANQUANT: $credentialsPath\n";
    }

    echo "\n🎉 Configuration FCM terminée!\n";
    echo "Les notifications seront maintenant envoyées via Firebase Cloud Messaging.\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
