<?php
// app/Helpers/SvgHelper.php
namespace App\Helpers;

use Illuminate\Support\Facades\File;

class SvgHelper
{
    public static function load($name)
    {
        $path = resource_path("svg/{$name}.svg");

        if (File::exists($path)) {
            $svgContent = File::get($path);
            return self::extractInnerSvgContent($svgContent);
        }

        return '';
    }

    public static function extractInnerSvgContent(string $svgContent): string
    {
        $cleanedContent = preg_replace('/\s+/', ' ', $svgContent);

        // Extraer todo el contenido dentro del <svg> (excluyendo la etiqueta svg misma)
        preg_match('/<svg[^>]*>(.*)<\/svg>/', $cleanedContent, $matches);

        if (!empty($matches[1])) {
            return trim($matches[1]);
        }

        return '';
    }

    public static function extractPathFromSvg(string $svgContent): string
    {
        $cleanedContent = preg_replace('/\s+/', ' ', $svgContent);

        // Extraer todos los paths completos con sus atributos
        preg_match_all('/<path\s+([^>]*)>/', $cleanedContent, $matches);

        if (!empty($matches[1])) {
            $paths = [];
            foreach ($matches[1] as $pathAttributes) {
                $paths[] = "<path {$pathAttributes}>";
            }
            return implode('', $paths);
        }

        return '';
    }

    public static function extractPathDataOnly(string $svgContent): string
    {
        $cleanedContent = preg_replace('/\s+/', ' ', $svgContent);

        // Buscar todos los paths y concatenar sus valores 'd'
        preg_match_all('/<path\s+[^>]*d="([^"]*)"[^>]*>/', $cleanedContent, $matches);

        if (!empty($matches[1])) {
            return implode(' ', $matches[1]);
        }

        return '';
    }

    public static function extractViewBox($name): string
    {
        $path = resource_path("svg/{$name}.svg");

        if (File::exists($path)) {
            $svgContent = File::get($path);
            // Buscar el viewBox en el SVG
            preg_match('/viewBox="([^"]*)"/', $svgContent, $matches);
            return $matches[1] ?? '';
        }

        return '';
    }

    // Nuevo método para detectar SVGs complejos - CORREGIDO
    public static function hasComplexStructure(string $svgContent): bool
    {
        // Buscar elementos que indiquen estructura compleja
        $complexElements = ['<g', '</g', '</svg>', '<circle', '<rect', '<polygon'];

        foreach ($complexElements as $element) {
            if (str_contains($svgContent, $element)) {
                return true;
            }
        }

        return false;
    }
}
