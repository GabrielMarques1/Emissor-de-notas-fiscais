<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro de Cache Headers para Assets
 * 
 * Adiciona headers de cache otimizados para arquivos estáticos
 */
class CacheHeadersFilter implements FilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Não faz nada no before
    }

    /**
     * {@inheritDoc}
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        
        // Verificar se é um asset estático
        if (!$this->isStaticAsset($path)) {
            return $response;
        }
        
        // Obter extensão do arquivo
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        // Obter configuração de cache
        $config = config('Assets');
        $headers = $config->getCacheHeaders($extension);
        
        // Adicionar headers ao response
        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }
        
        // Adicionar ETag se arquivo existir
        $filePath = FCPATH . ltrim($path, '/');
        if (file_exists($filePath)) {
            $etag = md5_file($filePath);
            $response->setHeader('ETag', "\"{$etag}\"");
            
            // Verificar If-None-Match
            $ifNoneMatch = $request->getHeaderLine('If-None-Match');
            if ($ifNoneMatch === "\"{$etag}\"") {
                return $response->setStatusCode(304); // Not Modified
            }
        }
        
        return $response;
    }
    
    /**
     * Verificar se é um asset estático
     */
    protected function isStaticAsset(string $path): bool
    {
        $staticExtensions = [
            'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
            'woff', 'woff2', 'ttf', 'eot', 'ico', 'json', 'xml'
        ];
        
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        return in_array(strtolower($extension), $staticExtensions);
    }
}

