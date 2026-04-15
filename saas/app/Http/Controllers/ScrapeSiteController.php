<?php

namespace App\Http\Controllers;

use App\Services\SocialScraperService;
use App\Models\Website;
use App\Models\Template;
use App\Services\TemplateEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScrapeSiteController extends Controller
{
    private SocialScraperService $scraper;

    public function __construct(SocialScraperService $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * POST /api/scrape-and-generate
     *
     * Accepts: { url: string }
     * Returns: { success, url, slug, business_name, source }
     */
    public function handle(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $url = $validated['url'];

        // 1. Detect source and extract business data
        try {
            $extracted = $this->scraper->extract($url);
        } catch (\Exception $e) {
            Log::error('Scraper extraction failed', ['url' => $url, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Could not extract business data from the provided URL. Please try again or fill details manually.',
                'error'   => $e->getMessage(),
            ], 422);
        }

        // 2. Require at least a business name
        if (empty($extracted['business_name'])) {
            return response()->json([
                'success'   => false,
                'message'   => 'Could not determine business name from this URL.',
                'extracted' => $extracted,
            ], 422);
        }

        $businessName = $extracted['business_name'];
        $category     = $extracted['category'] ?? 'Local Business';
        $slug         = Str::slug($businessName) . '-' . time();
        $siteUrl      = 'https://' . $slug . '.webze.site';

        // 3. Build the ai_content payload that our TemplateEngine understands
        $aiContent = [
            'category'   => $category,
            'rating'     => $extracted['rating'] ?? '4.8',
            'count'      => $extracted['count'] ?? '200+',
            'review'     => "Absolutely impressive! {$businessName} delivered far beyond our expectations.",
            'hero' => [
                'headline'    => $businessName,
                'subheadline' => $extracted['tagline'] ?? "Professional {$category} Services",
            ],
            'about' => [
                'description' => $extracted['description'] ?? "Professional {$category} services."
            ],
            'contact' => [
                'phone'   => $extracted['phone'] ?? '',
                'address' => $extracted['address'] ?? '',
            ],
            'seo' => [
                'title'       => "{$businessName} | {$category}",
                'description' => "Welcome to {$businessName} — {$extracted['description'] ?? 'Professional services you can trust.'}",
            ],
        ];

        // 4. Create the Website record (model auto-assigns template + generates static HTML)
        try {
            $website = Website::create([
                'business_name' => $businessName,
                'slug'          => $slug,
                'url'           => $siteUrl,
                'category'      => $category,
                'phone'         => $extracted['phone'] ?? null,
                'address'       => $extracted['address'] ?? null,
                'ai_content'    => $aiContent,
                'status'        => 'live',
            ]);
        } catch (\Exception $e) {
            Log::error('ScrapeSite website creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Website generation failed. Please try again.',
            ], 500);
        }

        return response()->json([
            'success'       => true,
            'url'           => $siteUrl,
            'slug'          => $slug,
            'website_id'    => $website->id,
            'business_name' => $businessName,
            'category'      => $category,
            'source'        => $extracted['source'] ?? 'generic',
            'phone'         => $extracted['phone'] ?? '',
        ]);
    }
}
