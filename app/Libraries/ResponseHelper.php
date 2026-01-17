<?php

/**
 * File Path: app/Libraries/ResponseHelper.php
 * 
 * Response Helper Library
 * Menyediakan standardisasi format response untuk API dan AJAX requests
 * 
 * @package    SIB-K
 * @subpackage Libraries
 * @category   Utilities
 * @author     Development Team
 * @created    2025-01-07
 * @updated    2025-01-07
 */

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;

class ResponseHelper
{
    /**
     * Success response with data
     * 
     * @param mixed  $data     Data to return
     * @param string $message  Success message
     * @param int    $code     HTTP status code
     * @return ResponseInterface
     */
    public static function success($data = null, string $message = 'Success', int $code = 200): ResponseInterface
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        return response()->setJSON($response)->setStatusCode($code);
    }

    /**
     * Error response
     * 
     * @param string $message Error message
     * @param mixed  $errors  Detailed errors (optional)
     * @param int    $code    HTTP status code
     * @return ResponseInterface
     */
    public static function error(string $message = 'An error occurred', $errors = null, int $code = 400): ResponseInterface
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->setJSON($response)->setStatusCode($code);
    }

    /**
     * Validation error response
     * 
     * @param array  $errors  Validation errors from validator
     * @param string $message Custom message
     * @return ResponseInterface
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): ResponseInterface
    {
        return self::error($message, $errors, 422);
    }

    /**
     * Not found response
     * 
     * @param string $message Custom message
     * @return ResponseInterface
     */
    public static function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        return self::error($message, null, 404);
    }

    /**
     * Unauthorized response
     * 
     * @param string $message Custom message
     * @return ResponseInterface
     */
    public static function unauthorized(string $message = 'Unauthorized access'): ResponseInterface
    {
        return self::error($message, null, 401);
    }

    /**
     * Forbidden response
     * 
     * @param string $message Custom message
     * @return ResponseInterface
     */
    public static function forbidden(string $message = 'Access forbidden'): ResponseInterface
    {
        return self::error($message, null, 403);
    }

    /**
     * Server error response
     * 
     * @param string $message Custom message
     * @param mixed  $errors  Error details
     * @return ResponseInterface
     */
    public static function serverError(string $message = 'Internal server error', $errors = null): ResponseInterface
    {
        return self::error($message, $errors, 500);
    }

    /**
     * Created response (for resource creation)
     * 
     * @param mixed  $data    Created resource data
     * @param string $message Success message
     * @return ResponseInterface
     */
    public static function created($data = null, string $message = 'Resource created successfully'): ResponseInterface
    {
        return self::success($data, $message, 201);
    }

    /**
     * No content response (for successful deletion)
     * 
     * @param string $message Success message
     * @return ResponseInterface
     */
    public static function noContent(string $message = 'Resource deleted successfully'): ResponseInterface
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        return response()->setJSON($response)->setStatusCode(204);
    }

    /**
     * Paginated response
     * 
     * @param array  $items       Paginated items
     * @param int    $total       Total items count
     * @param int    $perPage     Items per page
     * @param int    $currentPage Current page number
     * @param string $message     Success message
     * @return ResponseInterface
     */
    public static function paginated(
        array $items,
        int $total,
        int $perPage,
        int $currentPage,
        string $message = 'Data retrieved successfully'
    ): ResponseInterface {
        $data = [
            'items'        => $items,
            'pagination'   => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $currentPage,
                'total_pages'  => (int) ceil($total / $perPage),
                'from'         => (($currentPage - 1) * $perPage) + 1,
                'to'           => min($currentPage * $perPage, $total),
            ],
        ];

        return self::success($data, $message);
    }

    /**
     * Response with custom status code
     * 
     * @param bool   $success Success status
     * @param string $message Response message
     * @param mixed  $data    Response data
     * @param int    $code    HTTP status code
     * @return ResponseInterface
     */
    public static function custom(bool $success, string $message, $data = null, int $code = 200): ResponseInterface
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->setJSON($response)->setStatusCode($code);
    }

    /**
     * Redirect with flash message (for form submissions)
     * 
     * @param string $url     Redirect URL
     * @param string $message Flash message
     * @param string $type    Message type (success, error, warning, info)
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public static function redirect(string $url, string $message, string $type = 'success')
    {
        return redirect()->to($url)->with($type, $message);
    }

    /**
     * Redirect back with flash message
     * 
     * @param string $message Flash message
     * @param string $type    Message type (success, error, warning, info)
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public static function redirectBack(string $message, string $type = 'success')
    {
        return redirect()->back()->with($type, $message);
    }

    /**
     * Format exception for response
     * 
     * @param \Throwable $exception The exception
     * @param bool       $debug     Include debug info
     * @return ResponseInterface
     */
    public static function exception(\Throwable $exception, bool $debug = false): ResponseInterface
    {
        $message = $exception->getMessage();
        $errors = null;

        if ($debug) {
            $errors = [
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        log_message('error', '[EXCEPTION] ' . $message . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());

        return self::serverError($message, $errors);
    }

    /**
     * API response wrapper untuk backward compatibility
     * 
     * @param bool   $status  Success status
     * @param string $message Response message
     * @param mixed  $data    Response data
     * @param int    $code    HTTP status code
     * @return ResponseInterface
     */
    public static function json(bool $status, string $message, $data = null, int $code = 200): ResponseInterface
    {
        return self::custom($status, $message, $data, $code);
    }

    /**
     * Download response untuk file
     * 
     * @param string $filepath File path
     * @param string $filename Download filename
     * @return ResponseInterface
     */
    public static function download(string $filepath, string $filename = null): ResponseInterface
    {
        if (!file_exists($filepath)) {
            return self::notFound('File not found');
        }

        $filename = $filename ?? basename($filepath);

        return response()->download($filepath, null)->setFileName($filename);
    }

    /**
     * Streaming response untuk large files
     * 
     * @param string $filepath File path
     * @param string $mimeType MIME type
     * @return ResponseInterface
     */
    public static function stream(string $filepath, string $mimeType = 'application/octet-stream'): ResponseInterface
    {
        if (!file_exists($filepath)) {
            return self::notFound('File not found');
        }

        $response = response();
        $response->setHeader('Content-Type', $mimeType);
        $response->setHeader('Content-Length', filesize($filepath));
        $response->setBody(file_get_contents($filepath));

        return $response;
    }
}
