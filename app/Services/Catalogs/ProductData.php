<?php

namespace App\Services\Catalogs;

class ProductData
{
    public static function text(mixed $value): ?string
    {
        if (! is_scalar($value) || is_bool($value)) {
            return null;
        }
        $text = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text !== '' ? mb_substr($text, 0, 12000) : null;
    }

    public static function make(array $data): array
    {
        $fields = ['name', 'description', 'brand', 'model', 'code', 'presentation', 'price', 'currency', 'availability', 'image', 'url'];
        $product = [];
        foreach ($fields as $field) {
            $product[$field] = self::text($data[$field] ?? null);
        }
        $product['url'] = PublicUrl::displayLink($product['url']);
        $product['image'] = PublicUrl::displayLink($product['image']);
        $product['details'] = $data['details'] ?? [];

        return $product;
    }

    public static function dom(string $html): \DOMXPath
    {
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new \DOMXPath($dom);
    }
}
