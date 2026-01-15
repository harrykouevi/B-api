<?php

namespace Database\Seeders;

use App\Models\OptionGroup;
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
        OptionGroup::truncate();

        // Récupérer tous les ServiceTemplates
        $serviceTemplates = ServiceTemplate::all();

        if ($serviceTemplates->isEmpty()) {
            $this->command->warn('Aucun ServiceTemplate trouvé. Exécutez d\'abord ServiceTemplatesSeeder.');
            return;
        }

        // Définir les options pour chaque service
        $optionsData = [
            'Longueur' => [
                ['name' => 'Coupe courte', 'description' => 'Coupe courte et moderne', 'price' => 25.00],
                ['name' => 'Coupe mi-long', 'description' => 'Coupe mi-longue avec dégradé', 'price' => 30.00],
                ['name' => 'Coupe long', 'description' => 'Coupe longue avec effilage', 'price' => 35.00],
                ['name' => 'Frange incluse', 'description' => 'Ajout d\'une frange stylisée', 'price' => 5.00],
            ],
            'Epaisseur' => [
                ['name' => 'Coupe fine', 'description' => 'Coupe classique très courte', 'price' => 15.00],
                ['name' => 'Coupe moyenne', 'description' => 'Coupe avec dégradé progressif', 'price' => 18.00],
            ],
            'Type de mèche' => [
                ['name' => 'Naturelle', 'description' => 'Une seule couleur', 'price' => 40.00],
                ['name' => 'Synthetique', 'description' => 'Balayage naturel', 'price' => 60.00],
                ['name' => 'Pré-bouclé', 'description' => 'Retouche des racines uniquement', 'price' => 30.00],
                ['name' => 'Coloré', 'description' => 'Soin professionnel après coloration', 'price' => 15.00],
            ],
            'Style' => [
                ['name' => 'Classique', 'description' => 'Nettoyage en profondeur', 'price' => 35.00],
                ['name' => 'Fantaisie', 'description' => 'Hydratation intensive', 'price' => 40.00],
                ['name' => 'Evenementiel', 'description' => 'Traitement anti-rides', 'price' => 50.00],
            ]
            
        ];

        $count = 0;

        // Créer les options pour chaque ServiceTemplate
            
        foreach ($optionsData as $key => $options) {
            $m = OptionGroup::create([
                'name' => $key,
                'allow_multiple' => true
            ]);
            foreach ($serviceTemplates as $serviceTemplate) {

                foreach ($options as $option) {
                    OptionTemplate::create([
                        'name' => $option['name'],
                        'description' => $option['description'],
                        'price' => $option['price'],
                        'service_template_id' => $serviceTemplate->id,
                        'option_group_id' =>  $m->id
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