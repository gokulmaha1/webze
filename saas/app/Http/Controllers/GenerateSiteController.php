<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenerateSiteController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate([
            'business' => 'required|array',
            'business.name' => 'required|string',
            'ai_content' => 'required|array',
            'meta' => 'nullable|array'
        ]);

        $businessName = $data['business']['name'];
        $slug = Str::slug($businessName) . '-' . time();
        $url = 'https://' . $slug . '.webze.site';

        // Fetch the first template for auto-assignment (or default)
        try {
            $template = Template::first();
            $template_id = $template ? $template->id : null;
        } catch (\Exception $e) {
            $template_id = null; // fallback in case DB isn't fully migrated yet
        }

        // Store generated website in the database
        $website = Website::create([
            'business_name' => $businessName,
            'slug' => $slug,
            'url' => $url,
            'category' => $data['business']['category'] ?? null,
            'phone' => $data['business']['phone'] ?? null,
            'address' => $data['business']['address'] ?? null,
            'ai_content' => $data['ai_content'],
            'status' => 'live',
            'template_id' => $template_id,
            // owner_name isn't present in n8n payload directly under 'business', 
            // but we could map it if added in future.
        ]);

        return response()->json([
            'success' => true,
            'url' => $url,
            'slug' => $slug,
            'website_id' => $website->id
        ]);
    }
}
