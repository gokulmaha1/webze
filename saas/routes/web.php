<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/generate-site', [\App\Http\Controllers\GenerateSiteController::class, 'generate']);
Route::get('/track/whatsapp/{slug}', [\App\Http\Controllers\TrackingController::class, 'trackWhatsapp'])->name('track.whatsapp');

Route::get('/preview/{slug}', function($slug) {
    $website = \App\Models\Website::where('slug', $slug)->firstOrFail();
    if (!$website->template_id) return 'No template assigned yet.';
    
    $template = \App\Models\Template::find($website->template_id);
    if (!$template) return 'Template not found.';

    $engine = new \App\Services\TemplateEngine();
    
    // Merge ai_content and business info into a single cohesive data payload for the engine
    $data = array_merge($website->ai_content ?? [], [
        'business' => [
            'name' => $website->business_name,
            'category' => $website->category,
            'address' => $website->address,
            'phone' => $website->phone,
        ]
    ]);

    return $engine->render($template->html_structure, $data);
});
