<?php

namespace App\Services;

class TemplateEngine
{
    /**
     * Parse HTML string with simple placeholders and loops based on the structured data array.
     */
    public function render(string $template, array $data): string
    {
        // 1. Handle Loops: {{#list}} ... {{/list}}
        $template = preg_replace_callback('/\{\{#([a-zA-Z0-9_]+)\}\}(.*?)\{\{\/\1\}\}/s', function ($matches) use ($data) {
            $key = $matches[1];
            $content = $matches[2];
            $items = $this->getValue($data, $key);
            
            if (!is_array($items)) {
                return '';
            }

            $html = '';
            foreach ($items as $item) {
                if (is_array($item)) {
                    $itemHtml = $content;
                    foreach ($item as $k => $v) {
                        if (is_string($v) || is_numeric($v)) {
                            $itemHtml = str_replace('{{' . $k . '}}', $v, $itemHtml);
                        }
                    }
                    $html .= $itemHtml;
                } elseif (is_string($item) || is_numeric($item)) {
                    // For array of strings (like why_choose_us points)
                    $html .= str_replace('{{value}}', $item, $content);
                }
            }
            return $html;
        }, $template);

        // 2. Handle simple flat placeholders: {{business.name}}, {{hero.headline}}
        $template = preg_replace_callback('/\{\{([a-zA-Z0-9_\.]+)\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            $val = $this->getValue($data, $key);
            return (is_string($val) || is_numeric($val)) ? $val : '';
        }, $template);

        // 3. Inject Global Floating WhatsApp Tracking Button
        $whatsappTrackerHtml = '
        <a href="https://app.webze.site/track/whatsapp/{{business.slug}}" target="_blank" class="global-wa-float" style="position:fixed;width:60px;height:60px;bottom:40px;right:40px;background-color:#25d366;color:#FFF;border-radius:50px;text-align:center;font-size:30px;box-shadow: 2px 2px 15px rgba(0,0,0,0.2);z-index:9999;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:0.3s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
        </a>';
        
        $template = str_replace('</body>', $whatsappTrackerHtml . "\n" . '</body>', $template);

        // Map the slug placeholder explicitly
        $template = str_replace('{{business.slug}}', $data['business']['slug'] ?? '', $template);

        return $template;
    }

    private function getValue(array $data, string $key)
    {
        $keys = explode('.', $key);
        $value = $data;
        
        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        
        return $value;
    }
}
