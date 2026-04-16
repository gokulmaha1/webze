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
                'name'        => 'Catering & Cafe (Professional)',
                'slug'        => 'catering-pro',
                'description' => 'Bright, professional light-theme template for caterers and cafes',
                'is_premium'  => false,
                'file'        => 'catering-pro.html',
            ],
            [
                'name'        => 'Catering & Events (Modern)',
                'slug'        => 'catering-modern',
                'description' => 'Ultra-modern, bright and clear design based on contemporary catering landing pages',
                'is_premium'  => true,
                'file'        => 'catering-modern.html',
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
            [
                'name'        => 'Hotel & Resort',
                'slug'        => 'hotel-resort',
                'description' => 'Luxury template for hotels, resorts and accommodations',
                'is_premium'  => true,
                'file'        => 'hotel.html',
            ],
            [
                'name'        => 'Portfolio & Creative',
                'slug'        => 'portfolio-creative',
                'description' => 'Modern portfolio for graphic designers and agencies',
                'is_premium'  => true,
                'file'        => 'portfolio.html',
            ],
            [
                'name'        => 'Local Services',
                'slug'        => 'local-services',
                'description' => 'Trustworthy template for plumbers, electricians and trades',
                'is_premium'  => false,
                'file'        => 'services.html',
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

        $this->command->info('✅ 10 templates seeded successfully!');
    }
}
