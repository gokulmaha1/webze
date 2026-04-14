<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'        => 'Salon & Beauty Parlor',
                'slug'        => 'salon-beauty',
                'description' => 'Elegant template for salons and beauty parlors',
                'is_premium'  => false,
                'file'        => 'salon.html',
            ],
            [
                'name'        => 'Catering & Cafe',
                'slug'        => 'catering-cafe',
                'description' => 'Warm template for catering services and cafes',
                'is_premium'  => false,
                'file'        => 'catering.html',
            ],
            [
                'name'        => 'Real Estate',
                'slug'        => 'real-estate',
                'description' => 'Professional template for real estate agencies',
                'is_premium'  => false,
                'file'        => 'real-estate.html',
            ],
            [
                'name'        => 'Clinic & Healthcare',
                'slug'        => 'clinic-healthcare',
                'description' => 'Clean medical template for clinics and doctors',
                'is_premium'  => false,
                'file'        => 'clinic.html',
            ],
            [
                'name'        => 'Corporate',
                'slug'        => 'corporate',
                'description' => 'Premium corporate template for businesses',
                'is_premium'  => true,
                'file'        => 'corporate.html',
            ],
            [
                'name'        => 'Restaurant',
                'slug'        => 'restaurant',
                'description' => 'Elegant dark template for restaurants',
                'is_premium'  => false,
                'file'        => 'restaurant.html',
            ],
        ];

        foreach ($templates as $t) {
            $htmlPath = database_path('seeders/templates/' . $t['file']);
            $html     = file_exists($htmlPath) ? file_get_contents($htmlPath) : '<h1>{{business.name}}</h1>';

            Template::updateOrCreate(
                ['slug' => $t['slug']],
                [
                    'name'           => $t['name'],
                    'description'    => $t['description'],
                    'is_premium'     => $t['is_premium'],
                    'html_structure' => $html,
                ]
            );
        }

        $this->command->info('✅ 6 templates seeded successfully!');
    }
}
