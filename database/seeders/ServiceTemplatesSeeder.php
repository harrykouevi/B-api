<?php

namespace Database\Seeders;

use App\Models\ServiceTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTemplatesSeeder extends Seeder
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
        ServiceTemplate::truncate();

        // 10 ServiceTemplates simples
        $serviceTemplates = [
            [
                'name' => 'Coupe Femme',
                'description' => 'Coupe de cheveux classique pour femme avec shampoing et brushing',
                'category_id' => 6, // Remplace par l'ID de ta catégorie
            ],
            [
                'name' => 'Coupe Homme',
                'description' => 'Coupe de cheveux moderne pour homme',
                'category_id' => 5,
            ],
            [
                'name' => 'Coloration',
                'description' => 'Coloration complète des cheveux avec produits professionnels',
                'category_id' => 9,
            ],
            [
                'name' => 'Soin Visage',
                'description' => 'Soin complet du visage avec nettoyage et masque hydratant',
                'category_id' => 6,
            ],
            [
                'name' => 'Épilation Sourcils',
                'description' => 'Épilation et restructuration des sourcils',
                'category_id' => 7,
            ],
            [
                'name' => 'Manucure',
                'description' => 'Soin complet des mains avec lime, cuticules et vernis',
                'category_id' => 7,
            ],
            [
                'name' => 'Pédicure',
                'description' => 'Soin complet des pieds avec gommage et vernis',
                'category_id' => 8,
            ],
            [
                'name' => 'Massage Relaxant',
                'description' => 'Massage de détente pour évacuer le stress',
                'category_id' => 8,
            ],
            [
                'name' => 'Maquillage Soirée',
                'description' => 'Maquillage sophistiqué pour événements spéciaux',
                'category_id' => 8,
            ],
            [
                'name' => 'Taille de Barbe',
                'description' => 'Taille et mise en forme de la barbe',
                'category_id' => 9,
            ],
        ];

        // Insérer les données
        foreach ($serviceTemplates as $template) {
            ServiceTemplate::create($template);
        }

        // Réactiver les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('10 ServiceTemplates créés avec succès!');
    }
}