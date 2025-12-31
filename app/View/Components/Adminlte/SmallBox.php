<?php

namespace App\View\Components\Adminlte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Helpers\SvgHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SmallBox extends Component
{
    public string $bgColorClass;
    public ?string $customBgColor;
    public string $svgContent;
    public string $svgViewBox;
    public bool $useFullPath;

    public function __construct(
        public string $title = 'Title',
        public string $value = '0',
        public string $color = 'primary',
        public string $url = '#',
        public ?string $description = null,
        public string $viewBox = '0 0 24 24',
        ?string $customBgColor = null,
        public string $icon = '',
        public string $svgPath = ''
    ) {
        $this->customBgColor = $customBgColor;
        $this->bgColorClass = $customBgColor ? 'custom-bg-color' : $this->getBgColorClass($color);

        // Obtener tanto el contenido como el viewBox
        $svgData = $this->getSvgContent();
        $this->svgContent = $svgData['path'];
        $this->svgViewBox = $svgData['viewBox'];
        $this->useFullPath = $svgData['useFullPath'];
    }

    private function getBgColorClass(string $color): string
    {
        $colors = [
            'primary' => 'text-bg-primary',
            'success' => 'text-bg-success',
            'warning' => 'text-bg-warning',
            'danger' => 'text-bg-danger',
            'info' => 'text-bg-info',
            'secondary' => 'text-bg-secondary',
        ];

        return $colors[$color] ?? $colors['primary'];
    }

    private function getSvgContent(): array
    {
        $svgContent = '';
        $detectedViewBox = $this->viewBox;
        $useFullPath = false;

        if ($this->icon) {
            $filePath = SvgHelper::load($this->icon);

            if ($filePath) {
                // Verificar si el SVG tiene estructura compleja (grupos, etc.)
                $svgFileContent = File::get(resource_path("svg/{$this->icon}.svg"));

                if (SvgHelper::hasComplexStructure($svgFileContent)) {
                    $useFullPath = true;
                    $svgContent = $filePath; // Esto ahora contiene todo el contenido interno
                } elseif ($this->hasSvgAttributes($filePath)) {
                    $useFullPath = true;
                    $svgContent = $filePath;
                } else {
                    $svgContent = SvgHelper::extractPathDataOnly($svgFileContent);
                }

                $detectedViewBox = SvgHelper::extractViewBox($this->icon) ?: $this->viewBox;
            }
        }

        if (empty($svgContent) && $this->icon && config()->has("dashboardCards.svgIcons.{$this->icon}")) {
            $svgContent = config("dashboardCards.svgIcons.{$this->icon}");
        }

        if (empty($svgContent)) {
            $svgContent = $this->svgPath;
        }

        return [
            'path' => $svgContent,
            'viewBox' => $detectedViewBox,
            'useFullPath' => $useFullPath
        ];
    }

    private function hasSvgAttributes(string $svgPath): bool
    {
        // Verificar si el path contiene atributos adicionales como opacity, fill, etc.
        return preg_match('/opacity=|fill-rule=|clip-rule=|stroke=|transform=/', $svgPath) === 1;
    }

    public function render(): View|Closure|string
    {
        return view('components.adminlte.small-box');
    }
}
