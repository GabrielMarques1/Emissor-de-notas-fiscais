<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $idLogin = $session->get('id_login');
        $tipo = $session->get('tipo');

        // Se não está logado, redireciona para login
        if (!$idLogin || !$tipo) {
            return redirect()->to('/login');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
