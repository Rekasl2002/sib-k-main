<?php

/**
 * File Path: app/Filters/CorsFilter.php
 * 
 * CORS Filter
 * Menangani CORS headers untuk keamanan aplikasi
 * 
 * @package    SIB-K
 * @subpackage Filters
 * @category   Security
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    /**
     * Add CORS headers to response
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Handle preflight requests
        if ($request->getMethod() === 'options') {
            $response = service('response');
            return $this->setCorsHeaders($response);
        }
    }

    /**
     * Add CORS headers after response
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $this->setCorsHeaders($response);
    }

    /**
     * Set CORS headers
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function setCorsHeaders(ResponseInterface $response)
    {
        // Get allowed origins from environment or use default
        $allowedOrigins = env('CORS_ALLOWED_ORIGINS', '*');

        // Set CORS headers
        $response->setHeader('Access-Control-Allow-Origin', $allowedOrigins);
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        $response->setHeader('Access-Control-Max-Age', '86400'); // 24 hours

        return $response;
    }
}
