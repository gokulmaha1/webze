<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialScraperService
{
    private string $geminiKey;
    private string $placesKey;

    public function __construct()
    {
        $this->geminiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
        $this->placesKey = config('services.google_places.key', env('GOOGLE_PLACES_API_KEY', ''));
    }

    // ──────────────────────────────────────────────────────────────────────
    // MAIN ENTRY: detect source and extract business data
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detect the source platform from URL and extract business data.
     *
     * @return array{business_name, category, phone, address, description, source}
     */
    public function extract(string $url): array
    {
        $source = $this->detectSource($url);

        return match ($source) {
            'gmb'       => $this->extractGmb($url),
            'instagram' => $this->extractInstagram($url),
            'facebook'  => $this->extractFacebook($url),
            'linkedin'  => $this->extractLinkedin($url),
            default     => $this->extractGeneric($url, $source),
        };
    }

    public function detectSource(string $url): string
    {
        if (str_contains($url, 'instagram.com'))                                        return 'instagram';
        if (str_contains($url, 'maps.app.goo.gl') || str_contains($url, 'maps.google')) return 'gmb';
        if (str_contains($url, 'g.co/kgs') || str_contains($url, 'goo.gl/maps'))       return 'gmb';
        if (str_contains($url, 'facebook.com') || str_contains($url, 'fb.com'))         return 'facebook';
        if (str_contains($url, 'linkedin.com'))                                          return 'linkedin';
        return 'generic';
    }

    // ──────────────────────────────────────────────────────────────────────
    // GOOGLE MY BUSINESS — Using Places API for best accuracy
    // ──────────────────────────────────────────────────────────────────────

    private function extractGmb(string $url): array
    {
        // Try to resolve short URL to get place name
        $resolvedUrl = $this->resolveRedirect($url);

        // Extract name from map URL pattern: /maps/place/PLACE_NAME/
        preg_match('#/maps/place/([^/]+)#', urldecode($resolvedUrl), $matches);
        $placeName = isset($matches[1]) ? urldecode(str_replace('+', ' ', $matches[1])) : '';

        if ($placeName && $this->placesKey) {
            return $this->queryGooglePlaces($placeName);
        }

        // Fallback: fetch page HTML and use Gemini
        $html = $this->fetchHtml($resolvedUrl);
        return $this->geminiExtract($html, 'Google My Business', $url);
    }

    private function queryGooglePlaces(string $name): array
    {
        try {
            // Step 1: find place
            $findResp = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                'input'       => $name,
                'inputtype'   => 'textquery',
                'fields'      => 'place_id,name,formatted_address,formatted_phone_number,types,rating,user_ratings_total',
                'key'         => $this->placesKey,
            ]);

            $placeId = $findResp->json('candidates.0.place_id');
            if (!$placeId) {
                return $this->fallback($name, 'Local Business');
            }

            // Step 2: get details
            $detailResp = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields'   => 'name,formatted_address,formatted_phone_number,website,rating,user_ratings_total,editorial_summary,types',
                'key'      => $this->placesKey,
            ]);

            $result  = $detailResp->json('result', []);
            $types   = $result['types'] ?? [];
            $catMap  = $this->mapPlaceTypes($types);

            return [
                'business_name' => $result['name'] ?? $name,
                'category'      => $catMap,
                'phone'         => $result['formatted_phone_number'] ?? '',
                'address'       => $result['formatted_address'] ?? '',
                'description'   => $result['editorial_summary']['overview'] ?? "Professional {$catMap} services.",
                'rating'        => (string) ($result['rating'] ?? '4.5'),
                'count'         => (string) ($result['user_ratings_total'] ?? '100'),
                'source'        => 'gmb',
            ];
        } catch (\Exception $e) {
            Log::error('Google Places API error', ['error' => $e->getMessage()]);
            return $this->fallback($name, 'Local Business');
        }
    }

    private function mapPlaceTypes(array $types): string
    {
        $map = [
            'restaurant' => 'Restaurant', 'food' => 'Restaurant', 'cafe' => 'Cafe & Catering',
            'bakery'     => 'Bakery', 'meal_delivery' => 'Catering', 'catering' => 'Catering',
            'beauty_salon' => 'Salon & Beauty', 'hair_care' => 'Salon & Beauty',
            'spa'        => 'Spa & Wellness', 'gym' => 'Fitness & Gym',
            'doctor'     => 'Clinic & Healthcare', 'hospital' => 'Clinic & Healthcare',
            'dentist'    => 'Dental Clinic', 'pharmacy' => 'Pharmacy',
            'real_estate_agency' => 'Real Estate', 'lodging' => 'Hotel & Resort',
            'school'     => 'Education', 'lawyer' => 'Legal Services',
            'bank'       => 'Banking & Finance', 'insurance_agency' => 'Insurance',
            'store'      => 'Retail Store', 'shopping_mall' => 'Shopping',
            'electrician' => 'Electrician Services', 'plumber' => 'Plumbing Services',
        ];
        foreach ($types as $t) {
            if (isset($map[$t])) return $map[$t];
        }
        return 'Local Business';
    }

    // ──────────────────────────────────────────────────────────────────────
    // INSTAGRAM — oEmbed + Gemini fallback
    // ──────────────────────────────────────────────────────────────────────

    private function extractInstagram(string $url): array
    {
        // Extract handle from URL
        preg_match('#instagram\.com/([^/?]+)#', $url, $m);
        $handle = $m[1] ?? '';

        // Try fetching the public profile page and parsing with Gemini
        $profileUrl = "https://www.instagram.com/{$handle}/";
        $html       = $this->fetchHtml($profileUrl);

        if (strlen($html) > 500) {
            $data          = $this->geminiExtract($html, 'Instagram business profile', $url);
            $data['source'] = 'instagram';
            if (!$data['business_name'] && $handle) {
                $data['business_name'] = ucwords(str_replace(['.', '_', '-'], ' ', $handle));
            }
            return $data;
        }

        return $this->fallback(
            ucwords(str_replace(['.', '_', '-'], ' ', $handle)),
            'Business'
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // FACEBOOK — Gemini parse of public page
    // ──────────────────────────────────────────────────────────────────────

    private function extractFacebook(string $url): array
    {
        $html = $this->fetchHtml($url);
        $data = $this->geminiExtract($html, 'Facebook business page', $url);
        $data['source'] = 'facebook';
        return $data;
    }

    // ──────────────────────────────────────────────────────────────────────
    // LINKEDIN — best-effort Gemini parse
    // ──────────────────────────────────────────────────────────────────────

    private function extractLinkedin(string $url): array
    {
        $html = $this->fetchHtml($url);
        $data = $this->geminiExtract($html, 'LinkedIn company page', $url);
        $data['source'] = 'linkedin';
        return $data;
    }

    // ──────────────────────────────────────────────────────────────────────
    // GENERIC — any other URL
    // ──────────────────────────────────────────────────────────────────────

    private function extractGeneric(string $url, string $source): array
    {
        $html = $this->fetchHtml($url);
        $data = $this->geminiExtract($html, 'business website', $url);
        $data['source'] = $source;
        return $data;
    }

    // ──────────────────────────────────────────────────────────────────────
    // GEMINI AI EXTRACTION
    // ──────────────────────────────────────────────────────────────────────

    private function geminiExtract(string $html, string $contextHint, string $url): array
    {
        if (!$this->geminiKey || strlen($html) < 100) {
            return $this->fallback('', 'Local Business');
        }

        // Trim HTML to avoid token limits (keep first 8000 chars)
        $trimmed = substr(strip_tags($html), 0, 8000);

        $prompt = <<<PROMPT
You are an expert business data extractor. Given the following text scraped from a {$contextHint} page, extract structured business information.

Return ONLY a valid JSON object with these exact keys:
{
  "business_name": "exact business name (string)",
  "category": "business category like Restaurant, Salon, Clinic, Catering, Real Estate, etc. (string)",
  "phone": "phone number with country code if available (string, can be empty)",
  "address": "full address (string, can be empty)",
  "description": "2-3 sentence professional description of the business (string)",
  "tagline": "short catchy tagline (string)"
}

Source URL: {$url}

Scraped text:
{$trimmed}
PROMPT;

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.1,
                        'maxOutputTokens' => 512,
                    ],
                ]);

            $text = $response->json('candidates.0.content.parts.0.text', '');

            // Extract JSON from response
            preg_match('/\{.*\}/s', $text, $jsonMatch);
            if (!empty($jsonMatch[0])) {
                $parsed = json_decode($jsonMatch[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return array_merge($this->fallback('', 'Local Business'), $parsed);
                }
            }
        } catch (\Exception $e) {
            Log::error('Gemini extraction failed', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return $this->fallback('', 'Local Business');
    }

    // ──────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────

    private function fetchHtml(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(15)->get($url);

            return $response->successful() ? $response->body() : '';
        } catch (\Exception $e) {
            Log::warning('fetchHtml failed', ['url' => $url, 'error' => $e->getMessage()]);
            return '';
        }
    }

    private function resolveRedirect(string $url): string
    {
        try {
            // HEAD request follows redirects
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0',
            ])->timeout(10)->head($url);
            return $response->effectiveUri() ?? $url;
        } catch (\Exception $e) {
            return $url;
        }
    }

    private function fallback(string $name, string $category): array
    {
        return [
            'business_name' => $name,
            'category'      => $category,
            'phone'         => '',
            'address'       => '',
            'description'   => $name ? "Professional {$category} services by {$name}." : "Professional {$category} services.",
            'tagline'       => 'Quality Service You Can Trust',
            'rating'        => '4.8',
            'count'         => '200',
            'source'        => 'generic',
        ];
    }
}
