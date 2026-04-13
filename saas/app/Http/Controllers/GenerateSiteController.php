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
        $data = $request->validate([
            'business'        => 'required|array',
            'business.name'   => 'required|string',
            'ai_content'      => 'required|array',
            'meta'            => 'nullable|array'
        ]);

        $businessName = $data['business']['name'];
        $slug         = Str::slug($businessName) . '-' . time();
        $url          = 'https://' . $slug . '.webze.site';

        // Fetch the first template for auto-assignment
        try {
            $template    = Template::first();
            $template_id = $template ? $template->id : null;
        } catch (\Exception $e) {
            $template_id = null;
        }

        // Store in database
        $website = Website::create([
            'business_name' => $businessName,
            'slug'          => $slug,
            'url'           => $url,
            'category'      => $data['business']['category'] ?? null,
            'phone'         => $data['business']['phone'] ?? null,
            'address'       => $data['business']['address'] ?? null,
            'ai_content'    => $data['ai_content'],
            'status'        => 'live',
            'template_id'   => $template_id,
        ]);

        // ── Generate static HTML file at /var/www/webze/{slug}/index.html ──────
        $htmlGenerated = false;
        if ($template) {
            try {
                $engine  = new TemplateEngine();
                $payload = array_merge($data['ai_content'], [
                    'business' => [
                        'name'     => $businessName,
                        'category' => $data['business']['category'] ?? '',
                        'address'  => $data['business']['address'] ?? '',
                        'phone'    => $data['business']['phone'] ?? '',
                    ]
                ]);

                $html    = $engine->render($template->html_structure, $payload);
                $siteDir = '/var/www/webze/' . $slug;

                if (!is_dir($siteDir)) {
                    mkdir($siteDir, 0755, true);
                }

                file_put_contents($siteDir . '/index.html', $html);
                $htmlGenerated = true;

            } catch (\Exception $e) {
                \Log::error('Static site generation failed: ' . $e->getMessage());
            }
        }
        // ────────────────────────────────────────────────────────────────────────

        return response()->json([
            'success'        => true,
            'url'            => $url,
            'slug'           => $slug,
            'website_id'     => $website->id,
            'html_generated' => $htmlGenerated,
        ]);
    }
}
