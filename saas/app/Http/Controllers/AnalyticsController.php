<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsLog;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Handle tracking request from static website's JS script.
     */
    public function track(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'url'  => 'required|url',
        ]);

        $website = Website::where('slug', $request->slug)->first();

        if (!$website) {
            return response()->json(['message' => 'Website not found'], 404);
        }

        $referrer = $request->input('referrer', '');
        $source = 'direct';

        // Detect Source
        if (str_contains($referrer, 'google.')) {
            $source = 'google';
        } elseif (str_contains($referrer, 'facebook.com') || str_contains($referrer, 'fb.me')) {
            $source = 'facebook';
        } elseif (str_contains($referrer, 'instagram.com')) {
            $source = 'instagram';
        } elseif (str_contains($referrer, 'wa.me') || str_contains($referrer, 'whatsapp.com')) {
            $source = 'whatsapp';
        }

        // Special case: if the URL itself has a source param (e.g. from our tracking links)
        $urlSource = parse_url($request->url, PHP_URL_QUERY);
        if ($urlSource && str_contains($urlSource, 'source=')) {
            parse_str($urlSource, $queryParams);
            if (isset($queryParams['source'])) {
                $source = $queryParams['source'];
            }
        }

        AnalyticsLog::create([
            'website_id' => $website->id,
            'event_type' => 'pageview',
            'page_url'   => $request->url,
            'is_admin_visit' => $request->input('is_admin', false),
            'metadata' => [
                'referrer'   => $referrer,
                'source'     => $source,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'screen'     => $request->input('screen_res', 'unknown'),
            ]
        ]);

        return response()->json(['status' => 'success']);
    }
}
