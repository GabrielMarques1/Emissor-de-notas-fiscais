<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

/**
 * Endpoint de Ping - Verificar conexão com servidor
 */
class Ping extends ResourceController
{
    protected $format = 'json';

    /**
     * Retorna status do servidor (para verificação de conexão)
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function index()
    {
        return $this->respond([
            'status' => 'online',
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }
}

