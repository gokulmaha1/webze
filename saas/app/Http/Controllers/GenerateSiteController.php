<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\Template;
use App\Services\TemplateEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenerateSiteController extends Controller
{
    public function generate(Request $request)
    {
        // Support both nested and flat JSON payloads
        $payload = $request->all();

        // If payload is an array, take the first item (handles list-wrapped scrapers)
        if (count($payload) > 0 && array_key_exists(0, $payload)) {
            $payload = $payload[0];
        }
        
        if (isset($payload['business_name']) && !isset($payload['business'])) {
            $payload['business'] = [
                'name' => $payload['business_name'] ?? '',
                'category' => $payload['category'] ?? '',
                'phone' => $payload['phone'] ?? '',
                'address' => $payload['address'] ?? '',
            ];
            
            $payload['ai_content']['contact'] = [
                'phone' => $payload['phone'] ?? '',
                'address' => $payload['address'] ?? '',
            ];
            
            // Map other fields into ai_content as well
            if (!isset($payload['ai_content']['hero'])) {
                $payload['ai_content']['hero'] = [
                    'headline' => "Welcome to {$payload['business_name']}",
                    'subheadline' => "Professional services for {$payload['category']}",
                ];
            }
        }

        $request->replace($payload);

        $data = $request->validate([
            'business'        => 'required|array',
            'business.name'   => 'required|string',
            'ai_content'      => 'required|array',
            'meta'            => 'nullable|array'
        ]);

        $businessName = $data['business']['name'];
        $slug         = Str::slug($businessName) . '-' . time();
        $url          = 'https://' . $slug . '.webze.site';

        // Extract phone/address from ai_content if missing in top-level business
        $phone   = $data['business']['phone'] ?? ($data['ai_content']['contact']['phone'] ?? ($data['ai_content']['phone'] ?? null));
        $address = $data['business']['address'] ?? ($data['ai_content']['contact']['address'] ?? ($data['ai_content']['address'] ?? null));

        // Store in database. The Model's `creating` and `saved` observers 
        // will automatically detect the specific template and generate the HTML files!
        $website = Website::create([
            'business_name' => $businessName,
            'slug'          => $slug,
            'url'           => $url,
            'category'      => $data['business']['category'] ?? ($data['ai_content']['category'] ?? null),
            'phone'         => $phone,
            'address'       => $address,
            'ai_content'    => $data['ai_content'],
            'status'        => 'live',
        ]);

        return response()->json([
            'success'        => true,
            'url'            => $url,
            'slug'           => $slug,
            'website_id'     => $website->id,
            'phone'          => $phone, 
        ]);
    }
}
