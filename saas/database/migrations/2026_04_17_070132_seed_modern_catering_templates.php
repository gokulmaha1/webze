<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = [
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
        ];

        foreach ($templates as $t) {
            $htmlPath = database_path('seeders/templates/' . $t['file']);
            $html     = file_exists($htmlPath) ? file_get_contents($htmlPath) : '<h1>{{business.name}}</h1>';

            \App\Models\Template::updateOrCreate(
                ['slug' => $t['slug']],
                [
                    'name'           => $t['name'],
                    'description'    => $t['description'],
                    'is_premium'     => $t['is_premium'],
                    'html_structure' => $html,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Template::whereIn('slug', ['catering-pro', 'catering-modern'])->delete();
    }
};
