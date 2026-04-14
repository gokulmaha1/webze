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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappLogs()
    {
        return $this->hasMany(WhatsappLog::class);
    }

    public function analyticsLogs()
    {
        return $this->hasMany(AnalyticsLog::class);
    }

    protected static function booted()
    {
        static::creating(function ($website) {
            if (!$website->template_id && $website->category) {
                $website->template_id = self::guessTemplateId($website->category);
            }
            // Absolute fallback
            if (!$website->template_id) {
                $template = Template::first();
                $website->template_id = $template ? $template->id : null;
            }
        });

        static::saved(function ($website) {
            $website->generateStaticSite();
        });
    }

    protected static function guessTemplateId($category)
    {
        $cat = strtolower($category);
        
        $map = [
            'salon' => ['salon', 'beauty', 'spa', 'hair', 'barber', 'nails', 'massage', 'wellness', 'makeup', 'style'],
            'restaurant' => ['Restaurant supply store','restaurant', 'food', 'cafe', 'dining', 'bistro', 'eatery', 'pizza', 'burger', 'bakery', 'pub', 'bar', 'grill'],
            'catering' => ['catering', 'caterer', 'banquet', 'event', 'wedding', 'party', 'venue', 'planner'],
            'clinic' => ['clinic', 'health', 'medical', 'hospital', 'doctor', 'dental', 'care', 'therapy', 'pharmacy', 'vet', 'gym', 'fitness'],
            'real-estate' => ['real estate', 'property', 'realtor', 'broker', 'housing', 'estate', 'homes', 'hotel', 'resort', 'inn', 'hostel', 'airbnb', 'accommodation'],
            'corporate' => [
                'corporate', 'business', 'agency', 'consulting', 'tech', 'software', 'enterprise', 'finance', 'marketing', 
                'design', 'graphic', 'portfolio', 'freelance', 'studio', 'creative', 'art', 
                'plumbing', 'cleaning', 'electrician', 'contractor', 'repair', 'mechanic', 'logistics', 'b2b', 'retail', 'shop', 'store', 'supplier'
            ]
        ];

        foreach ($map as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($cat, $keyword)) {
                    $template = Template::where('slug', $slug)->first();
                    if ($template) return $template->id;
                }
            }
        }

        return null;
    }

    public function generateStaticSite()
    {
        if (!$this->slug) {
            return;
        }

        // Must reload relation if we just set template_id in creating hook
        $template = $this->template()->first();
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
                        'slug' => $this->slug,
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
