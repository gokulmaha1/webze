<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Website;
use App\Models\Template;

echo "Starting data fix...\n";

foreach (Website::all() as $website) {
    echo "Processing ID {$website->id}: {$website->business_name}\n";
    
    $ai = $website->ai_content;
    
    // 1. Fix Phone
    if (!$website->phone && isset($ai)) {
        $phone = $ai['contact']['phone'] ?? ($ai['phone'] ?? null);
        if ($phone) {
            $website->phone = $phone;
            echo " - Fixed phone: {$phone}\n";
        }
    }
    
    // 2. Fix Category
    if (!$website->category && isset($ai)) {
        $category = $ai['category'] ?? null;
        if ($category) {
            $website->category = $category;
            echo " - Fixed category: {$category}\n";
        }
    }
    
    // 3. Fix Template if it's currently Salon but should be Catering/Restaurant
    // We'll re-run guessTemplateId logic now that slugs are fixed
    if ($website->category) {
        $cat = strtolower($website->category);
        $slug = null;
        
        if (str_contains($cat, 'catering') || str_contains($cat, 'caterer')) {
            $slug = 'catering-cafe';
        } elseif (str_contains($cat, 'restaurant')) {
            $slug = 'restaurant';
        }
        
        if ($slug) {
            $template = Template::where('slug', $slug)->first();
            if ($template && $website->template_id != $template->id) {
                $website->template_id = $template->id;
                echo " - Fixed template to: {$slug}\n";
            }
        }
    }
    
    $website->save();
    
    // 4. Regenerate static files
    $website->generateStaticSite();
}

echo "Data fix completed.\n";
