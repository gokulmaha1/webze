<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WhatsappLog;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Track a WhatsApp click and redirect to the actual WhatsApp wa.me URL
     */
    public function trackWhatsapp($slug)
    {
        $website = Website::where('slug', $slug)->firstOrFail();

        // Log the click
        WhatsappLog::create([
            'website_id' => $website->id,
            'phone_number' => $website->phone,
            'is_clicked' => true,
        ]);

        // Clean phone number for wa.me link (remove strictly anything non-numeric, optional '+')
        $phone = preg_replace('/[^0-9]/', '', $website->phone);

        // Fallback or actual redirect
        if (empty($phone)) {
            return redirect($website->url); 
        }

        $whatsappUrl = "https://wa.me/{$phone}?text=Hello%20{$website->business_name},%20I%20found%20you%20on%20Webze!";
        
        return redirect()->away($whatsappUrl);
    }

    /**
     * Track an inbound visit (e.g. from WhatsApp shares) then redirect to the actual site
     */
    public function trackVisit($slug, Request $request)
    {
        $website = Website::where('slug', $slug)->firstOrFail();

        \App\Models\AnalyticsLog::create([
            'website_id' => $website->id,
            'event_type' => 'visit',
            'page_url' => $website->url,
            'is_admin_visit' => auth()->check(),
            'metadata' => [
                'source' => $request->query('source', 'whatsapp_notification'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]
        ]);

        return redirect()->away($website->url);
    }
}
