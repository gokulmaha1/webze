<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TemplateEngine;
use Illuminate\Support\Facades\Log;

class Website extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ai_content' => 'array',
        ];
    }
    
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    protected static function booted()
    {
        static::saved(function ($website) {
            $website->generateStaticSite();
        });
    }

    public function generateStaticSite()
    {
        if (!$this->slug) {
            return;
        }

        $template = $this->template;
        if (!$template) {
            $template = Template::first();
        }

        if ($template) {
            try {
                $engine = new TemplateEngine();
                $contentConfig = $this->ai_content ?? [];
                
                // Set default reviews/counts to prevent missing variable errors
                if (!isset($contentConfig['rating'])) $contentConfig['rating'] = '5.0';
                if (!isset($contentConfig['count'])) $contentConfig['count'] = '500+';
                if (!isset($contentConfig['review'])) $contentConfig['review'] = 'Absolutely phenomenal service and exceptional quality.';

                $payload = array_merge($contentConfig, [
                    'business' => [
                        'name' => $this->business_name,
                        'category' => $this->category ?? '',
                        'address' => $this->address ?? '',
                        'phone' => $this->phone ?? '',
                    ]
                ]);

                $html = $engine->render($template->html_structure, $payload);
                
                // Store in the correct location for nginx to serve
                $siteDir = '/var/www/webze/' . $this->slug;

                if (!is_dir($siteDir)) {
                    mkdir($siteDir, 0755, true);
                }

                file_put_contents($siteDir . '/index.html', $html);
            } catch (\Exception $e) {
                Log::error('Filament static site generation failed: ' . $e->getMessage());
            }
        }
    }
}
