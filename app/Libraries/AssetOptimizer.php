<?php

namespace App\Libraries;

/**
 * Otimizador de Assets - Compressão e Minificação
 * Reduz tamanho de CSS, JS e imagens para melhor performance
 */
class AssetOptimizer
{
    private $publicPath;
    private $cachePath;
    
    public function __construct()
    {
        $this->publicPath = FCPATH;
        $this->cachePath = WRITEPATH . 'cache/assets/';
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    /**
     * Minifica arquivo CSS
     */
    public function minifyCSS(string $cssContent): string
    {
        // Remove comentários
        $cssContent = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $cssContent);
        
        // Remove espaços desnecessários
        $cssContent = str_replace(["\r\n", "\r", "\n", "\t"], '', $cssContent);
        $cssContent = preg_replace('/\s+/', ' ', $cssContent);
        
        // Remove espaços ao redor de caracteres especiais
        $cssContent = str_replace([' {', '{ ', ' }', '} ', '; ', ' ;', ': ', ' :', ', ', ' ,'], 
                                 ['{', '{', '}', '}', ';', ';', ':', ':', ',', ','], $cssContent);
        
        // Remove último ponto e vírgula antes de }
        $cssContent = str_replace(';}', '}', $cssContent);
        
        return trim($cssContent);
    }
    
    /**
     * Minifica arquivo JavaScript
     */
    public function minifyJS(string $jsContent): string
    {
        // Remove comentários de linha única
        $jsContent = preg_replace('/\/\/.*$/m', '', $jsContent);
        
        // Remove comentários de múltiplas linhas
        $jsContent = preg_replace('/\/\*[\s\S]*?\*\//', '', $jsContent);
        
        // Remove quebras de linha e espaços extras
        $jsContent = preg_replace('/\s+/', ' ', $jsContent);
        
        // Remove espaços ao redor de operadores
        $jsContent = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $jsContent);
        
        return trim($jsContent);
    }
    
    /**
     * Combina e minifica múltiplos arquivos CSS
     */
    public function combineCSSFiles(array $files, string $outputName): string
    {
        $combinedContent = '';
        $lastModified = 0;
        
        foreach ($files as $file) {
            $filePath = $this->publicPath . ltrim($file, '/');
            
            if (file_exists($filePath)) {
                $combinedContent .= file_get_contents($filePath) . "\n";
                $lastModified = max($lastModified, filemtime($filePath));
            }
        }
        
        $minifiedContent = $this->minifyCSS($combinedContent);
        
        // Gerar hash para cache busting
        $hash = substr(md5($minifiedContent), 0, 8);
        $outputFile = $outputName . '.' . $hash . '.min.css';
        $outputPath = $this->cachePath . $outputFile;
        
        // Salvar arquivo minificado
        file_put_contents($outputPath, $minifiedContent);
        
        return '/writable/cache/assets/' . $outputFile;
    }
    
    /**
     * Combina e minifica múltiplos arquivos JavaScript
     */
    public function combineJSFiles(array $files, string $outputName): string
    {
        $combinedContent = '';
        $lastModified = 0;
        
        foreach ($files as $file) {
            $filePath = $this->publicPath . ltrim($file, '/');
            
            if (file_exists($filePath)) {
                $combinedContent .= file_get_contents($filePath) . ";\n";
                $lastModified = max($lastModified, filemtime($filePath));
            }
        }
        
        $minifiedContent = $this->minifyJS($combinedContent);
        
        // Gerar hash para cache busting
        $hash = substr(md5($minifiedContent), 0, 8);
        $outputFile = $outputName . '.' . $hash . '.min.js';
        $outputPath = $this->cachePath . $outputFile;
        
        // Salvar arquivo minificado
        file_put_contents($outputPath, $minifiedContent);
        
        return '/writable/cache/assets/' . $outputFile;
    }
    
    /**
     * Otimiza imagem (redimensiona e comprime)
     */
    public function optimizeImage(string $imagePath, int $maxWidth = 1920, int $quality = 85): bool
    {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Se imagem já é menor que o máximo, apenas comprimir
        if ($originalWidth <= $maxWidth) {
            return $this->compressImage($imagePath, $quality);
        }
        
        // Calcular novas dimensões
        $ratio = $maxWidth / $originalWidth;
        $newWidth = $maxWidth;
        $newHeight = (int) ($originalHeight * $ratio);
        
        // Criar imagem a partir do arquivo original
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Criar nova imagem redimensionada
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparência para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        // Redimensionar
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Salvar imagem otimizada
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($newImage, $imagePath, $quality);
                break;
            case 'image/png':
                // PNG quality é 0-9, converter de 0-100
                $pngQuality = (int) (9 - (($quality / 100) * 9));
                $result = imagepng($newImage, $imagePath, $pngQuality);
                break;
            case 'image/gif':
                $result = imagegif($newImage, $imagePath);
                break;
        }
        
        // Limpar memória
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $result;
    }
    
    /**
     * Comprime imagem sem redimensionar
     */
    private function compressImage(string $imagePath, int $quality): bool
    {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                return imagejpeg($image, $imagePath, $quality);
                
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                $pngQuality = (int) (9 - (($quality / 100) * 9));
                return imagepng($image, $imagePath, $pngQuality);
                
            default:
                return false;
        }
    }
    
    /**
     * Gera versão WebP de uma imagem
     */
    public function convertToWebP(string $imagePath): ?string
    {
        if (!function_exists('imagewebp')) {
            return null;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return null;
        }
        
        $mimeType = $imageInfo['mime'];
        $pathInfo = pathinfo($imagePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        
        // Criar imagem a partir do arquivo original
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            default:
                return null;
        }
        
        if (!$image) {
            return null;
        }
        
        // Converter para WebP
        $result = imagewebp($image, $webpPath, 85);
        imagedestroy($image);
        
        return $result ? $webpPath : null;
    }
    
    /**
     * Limpa cache de assets antigos
     */
    public function cleanOldAssets(int $maxAge = 86400): int
    {
        $deleted = 0;
        $cutoffTime = time() - $maxAge;
        
        $files = glob($this->cachePath . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Gera sprite CSS de ícones
     */
    public function generateIconSprite(array $iconPaths, string $outputName): array
    {
        $spriteWidth = 0;
        $spriteHeight = 0;
        $icons = [];
        $currentY = 0;
        
        // Analisar dimensões dos ícones
        foreach ($iconPaths as $name => $path) {
            if (!file_exists($path)) {
                continue;
            }
            
            $imageInfo = getimagesize($path);
            if (!$imageInfo) {
                continue;
            }
            
            $icons[$name] = [
                'path' => $path,
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'x' => 0,
                'y' => $currentY
            ];
            
            $spriteWidth = max($spriteWidth, $imageInfo[0]);
            $currentY += $imageInfo[1];
        }
        
        $spriteHeight = $currentY;
        
        // Criar imagem sprite
        $sprite = imagecreatetruecolor($spriteWidth, $spriteHeight);
        imagealphablending($sprite, false);
        imagesavealpha($sprite, true);
        
        // Fundo transparente
        $transparent = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
        imagefill($sprite, 0, 0, $transparent);
        
        // CSS para os ícones
        $css = ".icon-sprite { background-image: url('{$outputName}.png'); background-repeat: no-repeat; display: inline-block; }\n";
        
        // Adicionar cada ícone ao sprite
        foreach ($icons as $name => $icon) {
            $iconImage = null;
            
            switch (exif_imagetype($icon['path'])) {
                case IMAGETYPE_PNG:
                    $iconImage = imagecreatefrompng($icon['path']);
                    break;
                case IMAGETYPE_JPEG:
                    $iconImage = imagecreatefromjpeg($icon['path']);
                    break;
                case IMAGETYPE_GIF:
                    $iconImage = imagecreatefromgif($icon['path']);
                    break;
            }
            
            if ($iconImage) {
                imagecopy($sprite, $iconImage, $icon['x'], $icon['y'], 0, 0, $icon['width'], $icon['height']);
                imagedestroy($iconImage);
                
                // Adicionar CSS para este ícone
                $css .= ".icon-{$name} { width: {$icon['width']}px; height: {$icon['height']}px; background-position: -{$icon['x']}px -{$icon['y']}px; }\n";
            }
        }
        
        // Salvar sprite
        $spritePath = $this->cachePath . $outputName . '.png';
        $cssPath = $this->cachePath . $outputName . '.css';
        
        imagepng($sprite, $spritePath);
        imagedestroy($sprite);
        
        file_put_contents($cssPath, $css);
        
        return [
            'sprite_url' => '/writable/cache/assets/' . $outputName . '.png',
            'css_url' => '/writable/cache/assets/' . $outputName . '.css',
            'icons_count' => count($icons)
        ];
    }
    
    /**
     * Estatísticas de otimização
     */
    public function getOptimizationStats(): array
    {
        $stats = [
            'cache_files' => 0,
            'cache_size_bytes' => 0,
            'css_files' => 0,
            'js_files' => 0,
            'image_files' => 0
        ];
        
        $files = glob($this->cachePath . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $stats['cache_files']++;
                $stats['cache_size_bytes'] += filesize($file);
                
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                switch ($extension) {
                    case 'css':
                        $stats['css_files']++;
                        break;
                    case 'js':
                        $stats['js_files']++;
                        break;
                    case 'png':
                    case 'jpg':
                    case 'jpeg':
                    case 'gif':
                    case 'webp':
                        $stats['image_files']++;
                        break;
                }
            }
        }
        
        return $stats;
    }
}
