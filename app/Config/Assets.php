<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuração de Assets - CDN e Versionamento
 */
class Assets extends BaseConfig
{
    /**
     * Versão dos assets (atualizar a cada deploy)
     * Formato: v{major}.{minor}.{patch} ou timestamp
     */
    public string $version = 'v1.0.0';
    
    /**
     * URL do CDN (deixar vazio para usar local)
     * Exemplos:
     * - Cloudflare: 'https://cdn.seudominio.com'
     * - AWS CloudFront: 'https://d123456.cloudfront.net'
     * - BunnyCDN: 'https://seusite.b-cdn.net'
     */
    public string $cdnUrl = '';
    
    /**
     * Ativar CDN em produção
     */
    public bool $cdnEnabled = false;
    
    /**
     * Diretórios de assets que devem usar CDN
     */
    public array $cdnDirectories = [
        'theme',
        'assets',
        'pdv-assets'
    ];
    
    /**
     * Cache headers por tipo de arquivo (em segundos)
     */
    public array $cacheHeaders = [
        // Assets estáticos - cache longo (1 ano)
        'css'   => 31536000,
        'js'    => 31536000,
        'woff'  => 31536000,
        'woff2' => 31536000,
        'ttf'   => 31536000,
        'eot'   => 31536000,
        
        // Imagens - cache médio (30 dias)
        'jpg'   => 2592000,
        'jpeg'  => 2592000,
        'png'   => 2592000,
        'gif'   => 2592000,
        'svg'   => 2592000,
        'webp'  => 2592000,
        'ico'   => 2592000,
        
        // Outros - cache curto (1 dia)
        'json'  => 86400,
        'xml'   => 86400,
    ];
    
    /**
     * Usar hash de arquivo no versionamento
     * Se true, gera hash MD5 do conteúdo do arquivo
     * Ex: app.js?v=a1b2c3d4
     */
    public bool $useFileHash = false;
    
    /**
     * Comprimir assets automaticamente
     */
    public bool $compress = true;
    
    /**
     * Minificar HTML em produção
     */
    public bool $minifyHtml = false;
    
    /**
     * Preload de recursos críticos
     */
    public array $preload = [
        '/theme/dist/css/adminlte.min.css' => 'style',
        '/theme/plugins/fontawesome-free/css/all.min.css' => 'style',
        '/theme/plugins/jquery/jquery.min.js' => 'script',
    ];
    
    /**
     * DNS Prefetch para domínios externos
     */
    public array $dnsPrefetch = [
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
        'https://cdn.jsdelivr.net',
    ];
    
    /**
     * Gerar URL de asset com versionamento
     */
    public function assetUrl(string $path): string
    {
        // Remover barra inicial se houver
        $path = ltrim($path, '/');
        
        // Verificar se deve usar CDN
        $baseUrl = $this->shouldUseCdn($path) 
                   ? rtrim($this->cdnUrl, '/') 
                   : rtrim(base_url(), '/');
        
        // Gerar versão
        $version = $this->getVersion($path);
        
        // Construir URL
        $url = $baseUrl . '/' . $path;
        
        if ($version) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'v=' . $version;
        }
        
        return $url;
    }
    
    /**
     * Verificar se path deve usar CDN
     */
    protected function shouldUseCdn(string $path): bool
    {
        if (!$this->cdnEnabled || empty($this->cdnUrl)) {
            return false;
        }
        
        foreach ($this->cdnDirectories as $dir) {
            if (strpos($path, $dir) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obter versão do asset
     */
    protected function getVersion(string $path): string
    {
        if ($this->useFileHash) {
            $filePath = FCPATH . $path;
            
            if (file_exists($filePath)) {
                return substr(md5_file($filePath), 0, 8);
            }
        }
        
        return $this->version;
    }
    
    /**
     * Obter cache headers para tipo de arquivo
     */
    public function getCacheHeaders(string $extension): array
    {
        $maxAge = $this->cacheHeaders[$extension] ?? 86400; // 1 dia padrão
        
        return [
            'Cache-Control' => "public, max-age={$maxAge}, immutable",
            'Expires' => gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT',
            'Pragma' => 'public',
            'Vary' => 'Accept-Encoding',
        ];
    }
}

