<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /** @var CLIRequest|IncomingRequest */
    protected $request;

    /** @var list<string> */
    protected $helpers = ['app', 'auth', 'form', 'url', 'text', 'response', 'date', 'permission', 'notification', 'settings'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Wajib duluan
        parent::initController($request, $response, $logger);

        // Opsional karena sudah ada di $helpers, tapi aman kalau dibiarkan:
        // helper('settings');

        // Variabel global untuk semua view
        $renderer = service('renderer');
        $renderer->setVar('appName',     setting('app_name',     env('app.appName'),          'general'));
        $renderer->setVar('schoolName',  setting('school_name',  env('school.name'),          'general'));
        $renderer->setVar('logoPath',    setting('logo_path',    'assets/images/logo.png',    'branding'));
        $renderer->setVar('faviconPath', setting('favicon_path', 'assets/images/favicon.ico', 'branding'));

        // preload model/service di sini bila perlu...
        // $this->session = service('session');
    }
}
