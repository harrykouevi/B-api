<?php

namespace Database\Seeders;

use App\Models\OptionTemplate;
use App\Models\ServiceTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptionTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Désactiver les contraintes de clés étrangères temporairement
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Vider la table
        OptionTemplate::truncate();

        // Récupérer tous les ServiceTemplates
        $serviceTemplates = ServiceTemplate::all();

        if ($serviceTemplates->isEmpty()) {
            $this->command->warn('Aucun ServiceTemplate trouvé. Exécutez d\'abord ServiceTemplatesSeeder.');
            return;
        }

        // Définir les options pour chaque service
        $optionsData = [
            'Coupe Femme' => [
                ['name' => 'Coupe courte', 'description' => 'Coupe courte et moderne', 'price' => 25.00],
                ['name' => 'Coupe mi-long', 'description' => 'Coupe mi-longue avec dégradé', 'price' => 30.00],
                ['name' => 'Coupe long', 'description' => 'Coupe longue avec effilage', 'price' => 35.00],
                ['name' => 'Frange incluse', 'description' => 'Ajout d\'une frange stylisée', 'price' => 5.00],
            ],
            'Coupe Homme' => [
                ['name' => 'Coupe courte', 'description' => 'Coupe classique très courte', 'price' => 15.00],
                ['name' => 'Coupe dégradée', 'description' => 'Coupe avec dégradé progressif', 'price' => 18.00],
                ['name' => 'Taille barbe incluse', 'description' => 'Taille et mise en forme de la barbe', 'price' => 10.00],
            ],
            'Coloration' => [
                ['name' => 'Coloration simple', 'description' => 'Une seule couleur', 'price' => 40.00],
                ['name' => 'Coloration balayage', 'description' => 'Balayage naturel', 'price' => 60.00],
                ['name' => 'Coloration racines', 'description' => 'Retouche des racines uniquement', 'price' => 30.00],
                ['name' => 'Soin couleur inclus', 'description' => 'Soin professionnel après coloration', 'price' => 15.00],
            ],
            'Soin Visage' => [
                ['name' => 'Soin purifiant', 'description' => 'Nettoyage en profondeur', 'price' => 35.00],
                ['name' => 'Soin hydratant', 'description' => 'Hydratation intensive', 'price' => 40.00],
                ['name' => 'Soin anti-âge', 'description' => 'Traitement anti-rides', 'price' => 50.00],
                ['name' => 'Masque premium inclus', 'description' => 'Masque haut de gamme supplémentaire', 'price' => 20.00],
            ],
            'Épilation Sourcils' => [
                ['name' => 'Épilation simple', 'description' => 'Épilation basique', 'price' => 8.00],
                ['name' => 'Épilation + teinture', 'description' => 'Épilation avec teinture des sourcils', 'price' => 12.00],
                ['name' => 'Restructuration', 'description' => 'Restructuration complète', 'price' => 15.00],
            ],
            'Manucure' => [
                ['name' => 'Manucure classique', 'description' => 'Vernis standard', 'price' => 15.00],
                ['name' => 'Gel manucure', 'description' => 'Vernis semi-permanent', 'price' => 25.00],
                ['name' => 'Décoration nail art', 'description' => 'Motifs et décoration', 'price' => 10.00],
                ['name' => 'Massage des mains', 'description' => 'Massage relaxant inclus', 'price' => 8.00],
            ],
            'Pédicure' => [
                ['name' => 'Pédicure classique', 'description' => 'Vernis standard', 'price' => 20.00],
                ['name' => 'Gel pédicure', 'description' => 'Vernis semi-permanent', 'price' => 30.00],
                ['name' => 'Gommage intensif', 'description' => 'Gommage professionnel des talons', 'price' => 10.00],
                ['name' => 'Paraffine', 'description' => 'Soin hydratant à la paraffine', 'price' => 12.00],
            ],
            'Massage Relaxant' => [
                ['name' => 'Massage 30 min', 'description' => 'Demi-heure de massage', 'price' => 25.00],
                ['name' => 'Massage 60 min', 'description' => 'Une heure complète', 'price' => 45.00],
                ['name' => 'Massage dos/nuque', 'description' => 'Ciblé sur le haut du corps', 'price' => 20.00],
                ['name' => 'Aromathérapie', 'description' => 'Avec huiles essentielles premium', 'price' => 15.00],
            ],
            'Maquillage Soirée' => [
                ['name' => 'Maquillage complet', 'description' => 'Maquillage du visage entier', 'price' => 40.00],
                ['name' => 'Maquillage + essai', 'description' => 'Avec session d\'essai préalable', 'price' => 50.00],
                ['name' => 'Maquillage yeux', 'description' => 'Ciblé sur les yeux', 'price' => 25.00],
                ['name' => 'Lèvres spéciales', 'description' => 'Lip-art ou effet spécial', 'price' => 15.00],
            ],
            'Taille de Barbe' => [
                ['name' => 'Taille classique', 'description' => 'Taille standard de la barbe', 'price' => 12.00],
                ['name' => 'Design barbe', 'description' => 'Dessin ou motif personnalisé', 'price' => 18.00],
                ['name' => 'Taille + rasage contours', 'description' => 'Avec rasage des contours', 'price' => 15.00],
                ['name' => 'Traitement barbe', 'description' => 'Soin spécial pour la barbe', 'price' => 10.00],
            ],
        ];

        $count = 0;

        // Créer les options pour chaque ServiceTemplate
        foreach ($serviceTemplates as $serviceTemplate) {
            $serviceName = $serviceTemplate->name;

            if (isset($optionsData[$serviceName])) {
                foreach ($optionsData[$serviceName] as $option) {
                    OptionTemplate::create([
                        'name' => $option['name'],
                        'description' => $option['description'],
                        'price' => $option['price'],
                        'service_template_id' => $serviceTemplate->id,
                    ]);
                    $count++;
                }
            }
        }

        // Réactiver les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("{$count} OptionTemplates créés avec succès!");
    }
}