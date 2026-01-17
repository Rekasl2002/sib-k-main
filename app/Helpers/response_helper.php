<?php

/**
 * File Path: app/Helpers/response_helper.php
 * 
 * Global Response Helper Functions
 * Shortcut functions untuk ResponseHelper Library
 * 
 * Load this helper in BaseController atau via autoload
 * Usage: helper('response');
 * 
 * @package    SIB-K
 * @subpackage Helpers
 * @category   Utilities
 * @author     Development Team
 * @created    2025-01-07
 */

use App\Libraries\ResponseHelper;

if (!function_exists('json_success')) {
    /**
     * Quick success JSON response
     * 
     * @param mixed  $data    Data to return
     * @param string $message Success message
     * @param int    $code    HTTP status code
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_success($data = null, string $message = 'Success', int $code = 200)
    {
        return ResponseHelper::success($data, $message, $code);
    }
}

if (!function_exists('json_error')) {
    /**
     * Quick error JSON response
     * 
     * @param string $message Error message
     * @param mixed  $errors  Detailed errors
     * @param int    $code    HTTP status code
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_error(string $message = 'An error occurred', $errors = null, int $code = 400)
    {
        return ResponseHelper::error($message, $errors, $code);
    }
}

if (!function_exists('json_validation_error')) {
    /**
     * Quick validation error response
     * 
     * @param array  $errors  Validation errors
     * @param string $message Custom message
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_validation_error(array $errors, string $message = 'Validation failed')
    {
        return ResponseHelper::validationError($errors, $message);
    }
}

if (!function_exists('json_not_found')) {
    /**
     * Quick not found response
     * 
     * @param string $message Custom message
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_not_found(string $message = 'Resource not found')
    {
        return ResponseHelper::notFound($message);
    }
}

if (!function_exists('json_unauthorized')) {
    /**
     * Quick unauthorized response
     * 
     * @param string $message Custom message
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_unauthorized(string $message = 'Unauthorized access')
    {
        return ResponseHelper::unauthorized($message);
    }
}

if (!function_exists('json_forbidden')) {
    /**
     * Quick forbidden response
     * 
     * @param string $message Custom message
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_forbidden(string $message = 'Access forbidden')
    {
        return ResponseHelper::forbidden($message);
    }
}

if (!function_exists('json_created')) {
    /**
     * Quick created response
     * 
     * @param mixed  $data    Created resource
     * @param string $message Success message
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_created($data = null, string $message = 'Resource created successfully')
    {
        return ResponseHelper::created($data, $message);
    }
}

if (!function_exists('json_deleted')) {
    /**
     * Quick deleted response
     * 
     * @param string $message Success message
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_deleted(string $message = 'Resource deleted successfully')
    {
        return ResponseHelper::noContent($message);
    }
}

if (!function_exists('json_paginated')) {
    /**
     * Quick paginated response
     * 
     * @param array  $items       Items array
     * @param int    $total       Total items
     * @param int    $perPage     Items per page
     * @param int    $currentPage Current page
     * @param string $message     Success message
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function json_paginated(
        array $items,
        int $total,
        int $perPage,
        int $currentPage,
        string $message = 'Data retrieved successfully'
    ) {
        return ResponseHelper::paginated($items, $total, $perPage, $currentPage, $message);
    }
}

if (!function_exists('redirect_success')) {
    /**
     * Redirect with success message
     * 
     * @param string $url     Redirect URL
     * @param string $message Success message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function redirect_success(string $url, string $message)
    {
        return ResponseHelper::redirect($url, $message, 'success');
    }
}

if (!function_exists('redirect_error')) {
    /**
     * Redirect with error message
     * 
     * @param string $url     Redirect URL
     * @param string $message Error message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function redirect_error(string $url, string $message)
    {
        return ResponseHelper::redirect($url, $message, 'error');
    }
}

if (!function_exists('redirect_warning')) {
    /**
     * Redirect with warning message
     * 
     * @param string $url     Redirect URL
     * @param string $message Warning message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function redirect_warning(string $url, string $message)
    {
        return ResponseHelper::redirect($url, $message, 'warning');
    }
}

if (!function_exists('redirect_info')) {
    /**
     * Redirect with info message
     * 
     * @param string $url     Redirect URL
     * @param string $message Info message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function redirect_info(string $url, string $message)
    {
        return ResponseHelper::redirect($url, $message, 'info');
    }
}

if (!function_exists('back_with_success')) {
    /**
     * Redirect back with success message
     * 
     * @param string $message Success message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function back_with_success(string $message)
    {
        return ResponseHelper::redirectBack($message, 'success');
    }
}

if (!function_exists('back_with_error')) {
    /**
     * Redirect back with error message
     * 
     * @param string $message Error message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function back_with_error(string $message)
    {
        return ResponseHelper::redirectBack($message, 'error');
    }
}

if (!function_exists('back_with_warning')) {
    /**
     * Redirect back with warning message
     * 
     * @param string $message Warning message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function back_with_warning(string $message)
    {
        return ResponseHelper::redirectBack($message, 'warning');
    }
}

if (!function_exists('back_with_input')) {
    /**
     * Redirect back with input and error message
     * 
     * @param string $message Error message
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function back_with_input(string $message = 'Please check your input')
    {
        return redirect()->back()->withInput()->with('error', $message);
    }
}

if (!function_exists('format_response')) {
    /**
     * Format standard response array
     * 
     * @param bool   $success Success status
     * @param string $message Response message
     * @param mixed  $data    Response data
     * @return array
     */
    function format_response(bool $success, string $message, $data = null): array
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }
}

if (!function_exists('is_ajax_request')) {
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    function is_ajax_request(): bool
    {
        $request = \Config\Services::request();
        return $request->isAJAX();
    }
}

if (!function_exists('is_json_request')) {
    /**
     * Check if request expects JSON response
     * 
     * @return bool
     */
    function is_json_request(): bool
    {
        $request = \Config\Services::request();
        return $request->isAJAX() ||
            strpos($request->getHeaderLine('Accept'), 'application/json') !== false ||
            strpos($request->getHeaderLine('Content-Type'), 'application/json') !== false;
    }
}

if (!function_exists('api_response')) {
    /**
     * Smart response - JSON for AJAX, redirect for normal requests
     * 
     * @param bool   $success Success status
     * @param string $message Response message
     * @param mixed  $data    Response data
     * @param string $redirect_url Redirect URL for non-AJAX
     * @return mixed
     */
    function api_response(bool $success, string $message, $data = null, string $redirect_url = null)
    {
        if (is_json_request()) {
            return $success
                ? json_success($data, $message)
                : json_error($message, $data);
        }

        $type = $success ? 'success' : 'error';

        if ($redirect_url) {
            return redirect()->to($redirect_url)->with($type, $message);
        }

        return redirect()->back()->with($type, $message);
    }
}

if (!function_exists('handle_exception')) {
    /**
     * Handle exception and return appropriate response
     * 
     * @param \Throwable $e     The exception
     * @param string     $fallback_message Fallback message
     * @param bool       $redirect_back Redirect back instead of JSON
     * @return mixed
     */
    function handle_exception(\Throwable $e, string $fallback_message = 'An error occurred', bool $redirect_back = false)
    {
        // Log the exception
        log_message('error', '[EXCEPTION] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        // Determine if we should show detailed error
        $showDetails = ENVIRONMENT === 'development';
        $message = $showDetails ? $e->getMessage() : $fallback_message;

        if ($redirect_back) {
            return back_with_error($message);
        }

        if (is_json_request()) {
            return ResponseHelper::exception($e, $showDetails);
        }

        return redirect()->back()->with('error', $message);
    }
}

if (!function_exists('download_file')) {
    /**
     * Quick file download response
     * 
     * @param string $filepath File path
     * @param string $filename Download filename
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function download_file(string $filepath, string $filename = null)
    {
        return ResponseHelper::download($filepath, $filename);
    }
}

if (!function_exists('stream_file')) {
    /**
     * Stream file response
     * 
     * @param string $filepath File path
     * @param string $mimeType MIME type
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function stream_file(string $filepath, string $mimeType = 'application/octet-stream')
    {
        return ResponseHelper::stream($filepath, $mimeType);
    }
}
