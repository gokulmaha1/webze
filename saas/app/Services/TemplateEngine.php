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
