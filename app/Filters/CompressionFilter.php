<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro de Compressão GZIP para Responses
 * 
 * PERFORMANCE: Reduz tamanho de responses em 70-90%
 */
class CompressionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Não faz nada no before
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Verificar se cliente aceita gzip
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
        
        if (strpos($acceptEncoding, 'gzip') === false) {
            return $response;
        }
        
        // Não comprimir se já estiver comprimido
        if ($response->getHeaderLine('Content-Encoding')) {
            return $response;
        }
        
        // Não comprimir arquivos pequenos (< 1KB)
        $body = $response->getBody();
        if (strlen($body) < 1024) {
            return $response;
        }
        
        // Não comprimir certos tipos de conteúdo já comprimidos
        $contentType = $response->getHeaderLine('Content-Type');
        $skipTypes = ['image/', 'video/', 'audio/', 'application/zip', 'application/gzip'];
        
        foreach ($skipTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                return $response;
            }
        }
        
        // Comprimir com gzip
        $compressed = gzencode($body, 6); // Nível 6 = balance qualidade/velocidade
        
        if ($compressed === false) {
            log_message('warning', '[Compression] Falha ao comprimir response');
            return $response;
        }
        
        // Calcular economia
        $originalSize = strlen($body);
        $compressedSize = strlen($compressed);
        $savings = round((1 - ($compressedSize / $originalSize)) * 100, 2);
        
        log_message('debug', "[Compression] {$originalSize}B → {$compressedSize}B ({$savings}% economia)");
        
        // Atualizar response
        $response->setBody($compressed);
        $response->setHeader('Content-Encoding', 'gzip');
        $response->setHeader('Content-Length', (string) $compressedSize);
        $response->setHeader('Vary', 'Accept-Encoding');
        
        return $response;
    }
}

