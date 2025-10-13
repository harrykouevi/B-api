<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $categories = [
            [
                'id' => 1,
                'name' => 'COIFFURE&BARBIER',
                'slug' => Str::slug('COIFFURE&BARBIER'),
                'color' => '#ff9f43',
                'description' => '<p>Categories for all hair services</p>',
                'order' => 1,
                'featured' => 1,
                'parent_id' => null,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'BEAUTÉ  ESTHÉTIQUE',
                'slug' =>  Str::slug('BEAUTÉ  ESTHÉTIQUE'),
                'color' => '#0abde3',
                'description' => '<p>Categories for all hair services</p>',
                'order' => 2,
                'featured' => 0,
                'parent_id' => null,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'SPA & BIEN ÊTRE',
                'slug' => Str::slug('SPA & BIEN ÊTRE'),
                'color' => '#ee5253',
                'description' => '<p>Categories for all hair services</p>',
                'order' => 3,
                'featured' => 0,
                'parent_id' => null,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'VENTE DE PRODUITS COSMÉTIQUES & ESTHÉTIQUES',
                'slug' => Str::slug('VENTE DE PRODUITS COSMÉTIQUES & ESTHÉTIQUES'),
                'color' => '#10ac84',
                'description' => '<p>Category for Eyebrows</p>',
                'order' => 4,
                'featured' => 1,
                'parent_id' => null,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'COIFFURE & TRESSE FEMMES',
                'slug' => Str::slug('COIFFURE & TRESSE FEMMES'),
                'color' => '',
                'description' => 'COIFFURE SPECIALE POUR HOMME',
                'order' => 5,
                'featured' => 1,
                'parent_id' => 1,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'SOINS & MISE EN FORME DES CHEVEUX',
                'slug' => Str::slug('SOINS & MISE EN FORME DES CHEVEUX'),
                'color' => '',
                'description' => 'Coiffure spéciale pour l\'homme',
                'order' => 6,
                'featured' => 1,
                'parent_id' => 1,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 7,
                'name' => 'SHAMPOINGS & NETTOYAGES',
                'slug' => Str::slug('SHAMPOINGS & NETTOYAGES'),
                'color' => '',
                'description' => null,
                'order' => 7,
                'featured' => 1,
                'parent_id' => 2,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 8,
                'name' => 'LOCKS & STYLES PROTECTEURS',
                'slug' => Str::slug('LOCKS & STYLES PROTECTEURS'),
                'color' => '',
                'description' => null,
                'order' => 8,
                'featured' => 0,
                'parent_id' => 6,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 9,
                'name' => 'Coiffures Afro Naturelles & Protectrices',
                'slug' => Str::slug('Coiffures Afro Naturelles & Protectrices'),
                'color' => '',
                'description' => null,
                'order' => 0,
                'featured' => 0,
                'parent_id' => 8,
                'path' => null,
                'path_slugs' => null,
                'path_names' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Désactiver les clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('categories')->truncate();

        // Réactiver les clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('categories')->insert($categories);
    }
}
