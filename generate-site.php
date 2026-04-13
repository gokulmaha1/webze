<?php
header("Content-Type: application/json");

function respond($success, $data = []) {
    echo json_encode(array_merge(["success" => $success], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, ["error" => "Only POST allowed"]);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) respond(false, ["error" => "Invalid JSON"]);

$business = $input['business'] ?? null;
$ai = $input['ai_content'] ?? null;

if (!$business || !$ai) {
    respond(false, ["error" => "Missing business or ai_content"]);
}

$name = trim($business['name'] ?? '');
if (!$name) respond(false, ["error" => "Business name required"]);

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text . '-' . time();
}

$slug = generateSlug($name);

$basePath = "/var/www/webze/";
$sitePath = $basePath . $slug;
$filePath = $sitePath . "/index.html";

if (!file_exists($sitePath)) {
    if (!mkdir($sitePath, 0755, true)) {
        respond(false, ["error" => "Directory creation failed"]);
    }
}

// 🔥 Extract AI Content
$headline = $ai['hero']['headline'] ?? $name;
$subheadline = $ai['hero']['subheadline'] ?? '';
$cta = $ai['hero']['cta_text'] ?? 'Contact Now';
$image = $ai['hero']['image'] ?? 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1920&q=80';
$about = $ai['about']['description'] ?? '';
$services = $ai['services'] ?? [];
$why = $ai['why_choose_us'] ?? [];
$testimonialsArray = $ai['testimonials'] ?? [];
$phone = $business['phone'] ?? '';
$address = $business['address'] ?? '';
$mapEmbed = $business['map_embed'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d115133.010168434!2d-80.05054045059633!3d43.83404780517565!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b157bd5eb2e0b%3A0x6e788c0a373b5ae6!2sCaledon%2C%20ON!5e0!3m2!1sen!2sca!4v1713133866160!5m2!1sen!2sca';
$seoTitle = $ai['seo']['title'] ?? $name;
$seoDesc = $ai['seo']['description'] ?? '';

$whatsappLink = "https://wa.me/91" . preg_replace('/\D/', '', $phone);

// 🔥 Build Testimonials HTML
$testimonials = '';
if (!empty($testimonialsArray)) {
    $testimonials .= "<div class='grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto'>";
    foreach ($testimonialsArray as $t) {
        $client = $t['client_name'] ?? 'Client';
        $review = $t['review'] ?? '';
        $testimonials .= "
        <div class='p-6 bg-white rounded-2xl shadow-lg text-left'>
            <p class='text-gray-600 italic'>\"{$review}\"</p>
            <h4 class='text-lg font-bold mt-4'>- {$client}</h4>
        </div>";
    }
    $testimonials .= "</div>";
} else {
    $testimonials = "<p class='text-gray-500'>No testimonials yet.</p>";
}

// 🔥 Build Services HTML
$servicesHtml = '';
foreach ($services as $s) {
    $servicesHtml .= "
    <div class='p-6 bg-white rounded-2xl shadow-lg hover:scale-105 transition'>
        <h3 class='text-xl font-semibold mb-2'>{$s['icon']} {$s['name']}</h3>
        <p class='text-gray-600'>{$s['description']}</p>
    </div>";
}

// 🔥 Why Choose Us
$whyHtml = '';
foreach ($why as $w) {
    $whyHtml .= "<li class='mb-2'>✔ {$w}</li>";
}

// 🔥 FINAL HTML
$html = "
<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>{$seoTitle}</title>
<meta name='description' content='{$seoDesc}'>

<script src='https://cdn.tailwindcss.com'></script>
<link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap' rel='stylesheet'>

<style>
html { scroll-behavior: smooth; }
body { font-family: 'Inter', sans-serif; }

.hero {
  background-image: url('{$image}');
  background-size: cover;
  background-position: center;
}

.overlay {
  background: rgba(0,0,0,0.6);
}

.fade-in {
  animation: fade 1.5s ease-in;
}

@keyframes fade {
  from {opacity:0; transform:translateY(20px);}
  to {opacity:1; transform:translateY(0);}
}
</style>
</head>

<body class='pt-20 bg-gray-50 text-gray-800'>

<!-- 🔥 NAVBAR -->
<header class='fixed top-0 left-0 w-full z-50 bg-black/60 backdrop-blur-lg text-white'>
  <div class='max-w-7xl mx-auto flex justify-between items-center px-6 py-4'>
    
    <h1 class='text-xl font-bold'>{$name}</h1>

    <!-- Desktop Menu -->
    <nav class='hidden md:flex gap-6'>
      <a href='#about' class='hover:text-blue-400'>About</a>
      <a href='#services' class='hover:text-blue-400'>Services</a>
      <a href='#contact' class='hover:text-blue-400'>Contact</a>
    </nav>

    <!-- CTA -->
    <a href='{$whatsappLink}' target='_blank'
       class='bg-green-500 px-4 py-2 rounded-lg hidden md:block'>
       WhatsApp
    </a>

    <!-- Mobile Button -->
    <button id='menuBtn' class='md:hidden text-2xl'>☰</button>

  </div>

  <!-- Mobile Menu -->
  <div id='mobileMenu' class='hidden flex flex-col bg-black text-white px-6 pb-4'>
    <a href='#about' class='py-2'>About</a>
    <a href='#services' class='py-2'>Services</a>
    <a href='#contact' class='py-2'>Contact</a>
    <a href='{$whatsappLink}' class='py-2 text-green-400'>WhatsApp</a>
  </div>
</header>

<!-- 🔥 HERO -->
<section class='hero h-screen flex items-center justify-center text-center text-white'>
  <div class='overlay absolute inset-0'></div>
  <div class='relative z-10 fade-in px-6'>
    <h1 class='text-4xl md:text-7xl font-bold mb-4'>{$headline}</h1>
    <p class='max-w-2xl mx-auto mb-6'>{$subheadline}</p>

    <div class='flex flex-col md:flex-row justify-center gap-4'>
      <a href='tel:{$phone}' class='bg-blue-500 px-6 py-3 rounded-xl shadow-lg'>📞 {$cta}</a>
      <a href='{$whatsappLink}' target='_blank' class='bg-green-500 px-6 py-3 rounded-xl shadow-lg'>💬 WhatsApp</a>
    </div>
  </div>
</section>

<!-- 🔥 ABOUT -->
<section id='about' class='py-20 px-6 text-center fade-in'>
  <h2 class='text-4xl font-bold mb-6'>About Us</h2>
  <p class='max-w-3xl mx-auto text-gray-600'>{$about}</p>
</section>

<!-- 🔥 SERVICES -->
<section id='services' class='py-20 px-6 bg-gray-100'>
  <h2 class='text-4xl font-bold text-center mb-12'>Our Services</h2>
  <div class='grid md:grid-cols-3 gap-8 max-w-6xl mx-auto'>
    {$servicesHtml}
  </div>
</section>

<!-- 🔥 TESTIMONIALS -->
<section class='py-20 px-6 text-center'>
  <h2 class='text-4xl font-bold mb-10'>What Our Clients Say</h2>
  {$testimonials}
</section>

<!-- 🔥 WHY -->
<section class='py-20 px-6 bg-gray-100 text-center'>
  <h2 class='text-4xl font-bold mb-6'>Why Choose Us</h2>
  <ul class='max-w-xl mx-auto text-left'>{$whyHtml}</ul>
</section>

<!-- 🔥 MAP -->
<section class='py-20 px-6'>
  <h2 class='text-4xl font-bold text-center mb-6'>Find Us</h2>
  <div class='max-w-4xl mx-auto'>
    <iframe src='{$mapEmbed}' width='100%' height='300' style='border:0;' allowfullscreen loading='lazy'></iframe>
  </div>
</section>

<!-- 🔥 CONTACT -->
<section id='contact' class='py-20 px-6 bg-gray-900 text-white text-center'>
  <h2 class='text-4xl font-bold mb-4'>Contact Us</h2>
  <p>{$address}</p>
  <p class='mt-2'>📞 {$phone}</p>
</section>

<!-- 🔥 FLOATING WHATSAPP -->
<a href='{$whatsappLink}' target='_blank'
   class='fixed bottom-6 right-6 bg-green-500 text-white p-4 rounded-full shadow-xl text-xl animate-bounce'>
   💬
</a>

<!-- 🔥 MOBILE MENU SCRIPT -->
<script>
const btn = document.getElementById('menuBtn');
const menu = document.getElementById('mobileMenu');

btn.addEventListener('click', () => {
  menu.classList.toggle('hidden');
});
</script>

</body>
</html>
";

// Write file
if (!file_put_contents($filePath, $html)) {
    respond(false, ["error" => "Failed to write file"]);
}

$url = "https://{$slug}.webze.site";

respond(true, [
    "url" => $url,
    "slug" => $slug
]);