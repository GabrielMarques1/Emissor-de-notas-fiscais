<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = service('throttler');
        $ip = $request->getIPAddress() ?: 'unknown';
        $ok = $throttler->check('api:' . $ip, 60, MINUTE); // 60 req/min por IP
        if (! $ok) {
            return service('response')->setStatusCode(429)->setJSON(['error' => 'Too Many Requests']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}


