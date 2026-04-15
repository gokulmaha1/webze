<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/generate-site', [\App\Http\Controllers\GenerateSiteController::class, 'generate']);
Route::get('/track/whatsapp/{slug}', [\App\Http\Controllers\TrackingController::class, 'trackWhatsapp'])->name('track.whatsapp');
Route::get('/v/{slug}',            [\App\Http\Controllers\TrackingController::class, 'trackVisit'])->name('track.visit.short');
Route::get('/visit/{slug}',        [\App\Http\Controllers\TrackingController::class, 'trackVisit'])->name('track.visit');

// ─── Cashfree Payment Routes ───────────────────────────────────────────────
// createOrder and callback require auth; webhook is public (CSRF exempt in bootstrap/app.php)
Route::post('/payment/create-order', [PaymentController::class, 'createOrder'])->name('payment.create-order');
Route::get('/payment/callback',      [PaymentController::class, 'callback'])->name('payment.callback');
Route::post('/payment/webhook',      [PaymentController::class, 'webhook'])->name('payment.webhook');
Route::get('/p/{order_id}',          [PaymentController::class, 'showCheckout'])->name('payment.pay');
Route::get('/pay/{order_id}',        [PaymentController::class, 'showCheckout']);

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
