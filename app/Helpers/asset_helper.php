<?php

/**
 * Helper de Assets - CDN e Versionamento
 */

if (!function_exists('asset')) {
    /**
     * Gera URL de asset com versionamento e CDN
     * 
     * @param string $path Caminho do asset (ex: 'theme/dist/css/adminlte.min.css')
     * @return string URL completa com versão
     */
    function asset(string $path): string
    {
        $config = config('Assets');
        return $config->assetUrl($path);
    }
}

if (!function_exists('cdn_url')) {
    /**
     * Gera URL do CDN
     * 
     * @param string $path
     * @return string
     */
    function cdn_url(string $path): string
    {
        $config = config('Assets');
        
        if ($config->cdnEnabled && !empty($config->cdnUrl)) {
            return rtrim($config->cdnUrl, '/') . '/' . ltrim($path, '/');
        }
        
        return base_url($path);
    }
}

if (!function_exists('asset_version')) {
    /**
     * Retorna versão atual dos assets
     * 
     * @return string
     */
    function asset_version(): string
    {
        $config = config('Assets');
        return $config->version;
    }
}

if (!function_exists('preload_tags')) {
    /**
     * Gera tags de preload para recursos críticos
     * 
     * @return string HTML com tags <link rel="preload">
     */
    function preload_tags(): string
    {
        $config = config('Assets');
        $tags = [];
        
        foreach ($config->preload as $url => $type) {
            $assetUrl = asset($url);
            $as = $type === 'style' ? 'style' : 'script';
            $tags[] = "<link rel=\"preload\" href=\"{$assetUrl}\" as=\"{$as}\">";
        }
        
        return implode("\n", $tags);
    }
}

if (!function_exists('dns_prefetch_tags')) {
    /**
     * Gera tags de DNS prefetch
     * 
     * @return string HTML com tags <link rel="dns-prefetch">
     */
    function dns_prefetch_tags(): string
    {
        $config = config('Assets');
        $tags = [];
        
        foreach ($config->dnsPrefetch as $domain) {
            $tags[] = "<link rel=\"dns-prefetch\" href=\"{$domain}\">";
        }
        
        return implode("\n", $tags);
    }
}

if (!function_exists('cache_headers')) {
    /**
     * Define cache headers para assets
     * 
     * @param string $filePath Caminho do arquivo
     * @return void
     */
    function cache_headers(string $filePath): void
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $config = config('Assets');
        $headers = $config->getCacheHeaders($extension);
        
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }
}

if (!function_exists('inline_critical_css')) {
    /**
     * Gera CSS crítico inline para above-the-fold
     * 
     * @param string $cssPath
     * @return string
     */
    function inline_critical_css(string $cssPath): string
    {
        $fullPath = FCPATH . ltrim($cssPath, '/');
        
        if (file_exists($fullPath)) {
            $css = file_get_contents($fullPath);
            return "<style>{$css}</style>";
        }
        
        return '';
    }
}

if (!function_exists('defer_script')) {
    /**
     * Gera tag script com defer
     * 
     * @param string $path
     * @return string
     */
    function defer_script(string $path): string
    {
        $url = asset($path);
        return "<script src=\"{$url}\" defer></script>";
    }
}

if (!function_exists('async_script')) {
    /**
     * Gera tag script com async
     * 
     * @param string $path
     * @return string
     */
    function async_script(string $path): string
    {
        $url = asset($path);
        return "<script src=\"{$url}\" async></script>";
    }
}

if (!function_exists('webp_image')) {
    /**
     * Gera tag picture com fallback WebP
     * 
     * @param string $imagePath
     * @param string $alt
     * @param string $class
     * @return string
     */
    function webp_image(string $imagePath, string $alt = '', string $class = ''): string
    {
        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $imagePath);
        $webpUrl = asset($webpPath);
        $fallbackUrl = asset($imagePath);
        
        $classAttr = $class ? " class=\"{$class}\"" : '';
        
        return <<<HTML
        <picture>
            <source srcset="{$webpUrl}" type="image/webp">
            <img src="{$fallbackUrl}" alt="{$alt}"{$classAttr}>
        </picture>
        HTML;
    }
}

if (!function_exists('optimized_css')) {
    /**
     * Combina e minifica arquivos CSS
     * 
     * @param array $files Lista de arquivos CSS
     * @param string $name Nome do arquivo de saída
     * @return string URL do arquivo otimizado
     */
    function optimized_css(array $files, string $name = 'combined'): string
    {
        static $optimizer = null;
        
        if ($optimizer === null) {
            $optimizer = new \App\Libraries\AssetOptimizer();
        }
        
        return $optimizer->combineCSSFiles($files, $name);
    }
}

if (!function_exists('optimized_js')) {
    /**
     * Combina e minifica arquivos JavaScript
     * 
     * @param array $files Lista de arquivos JS
     * @param string $name Nome do arquivo de saída
     * @return string URL do arquivo otimizado
     */
    function optimized_js(array $files, string $name = 'combined'): string
    {
        static $optimizer = null;
        
        if ($optimizer === null) {
            $optimizer = new \App\Libraries\AssetOptimizer();
        }
        
        return $optimizer->combineJSFiles($files, $name);
    }
}

if (!function_exists('responsive_image')) {
    /**
     * Gera tag img com srcset responsivo
     * 
     * @param string $imagePath Caminho base da imagem
     * @param array $sizes Tamanhos disponíveis ['320w', '768w', '1200w']
     * @param string $alt Texto alternativo
     * @param string $class Classes CSS
     * @return string HTML da imagem responsiva
     */
    function responsive_image(string $imagePath, array $sizes, string $alt = '', string $class = ''): string
    {
        $pathInfo = pathinfo($imagePath);
        $srcsetParts = [];
        
        foreach ($sizes as $size) {
            $width = (int) str_replace('w', '', $size);
            $responsivePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $width . 'w.' . $pathInfo['extension'];
            $srcsetParts[] = asset($responsivePath) . ' ' . $size;
        }
        
        $srcset = implode(', ', $srcsetParts);
        $src = asset($imagePath);
        $classAttr = $class ? " class=\"{$class}\"" : '';
        
        return "<img src=\"{$src}\" srcset=\"{$srcset}\" alt=\"{$alt}\"{$classAttr}>";
    }
}

if (!function_exists('lazy_image')) {
    /**
     * Gera imagem com lazy loading
     * 
     * @param string $imagePath
     * @param string $alt
     * @param string $class
     * @param string $placeholder Placeholder enquanto carrega
     * @return string
     */
    function lazy_image(string $imagePath, string $alt = '', string $class = '', string $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E'): string
    {
        $src = asset($imagePath);
        $classAttr = $class ? " class=\"{$class}\"" : '';
        
        return "<img src=\"{$placeholder}\" data-src=\"{$src}\" alt=\"{$alt}\"{$classAttr} loading=\"lazy\">";
    }
}

if (!function_exists('critical_css_inline')) {
    /**
     * Inclui CSS crítico inline para performance
     * 
     * @param array $criticalFiles Arquivos CSS críticos
     * @return string CSS inline minificado
     */
    function critical_css_inline(array $criticalFiles): string
    {
        static $optimizer = null;
        
        if ($optimizer === null) {
            $optimizer = new \App\Libraries\AssetOptimizer();
        }
        
        $combinedCSS = '';
        
        foreach ($criticalFiles as $file) {
            $filePath = FCPATH . ltrim($file, '/');
            if (file_exists($filePath)) {
                $combinedCSS .= file_get_contents($filePath) . "\n";
            }
        }
        
        $minifiedCSS = $optimizer->minifyCSS($combinedCSS);
        
        return "<style>{$minifiedCSS}</style>";
    }
}

if (!function_exists('preload_font')) {
    /**
     * Gera preload para fontes
     * 
     * @param string $fontPath
     * @param string $format woff2, woff, ttf
     * @return string
     */
    function preload_font(string $fontPath, string $format = 'woff2'): string
    {
        $url = asset($fontPath);
        return "<link rel=\"preload\" href=\"{$url}\" as=\"font\" type=\"font/{$format}\" crossorigin>";
    }
}

