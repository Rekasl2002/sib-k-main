<?php

/**
 * File Path: app/Config/App.php
 * 
 * Application Configuration
 * Mengkonfigurasi pengaturan utama aplikasi SIB-K
 * 
 * @package    SIB-K
 * @subpackage Config
 * @category   Configuration
 * @author     Development Team
 * @created    2025-01-01
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Base Site URL
     * --------------------------------------------------------------------------
     *
     * URL to your CodeIgniter root. Typically this will be your base URL,
     * WITH a trailing slash:
     *
     *    http://example.com/
     */
    public string $baseURL = 'http://localhost:8080/';

    /**
     * --------------------------------------------------------------------------
     * Application Name
     * --------------------------------------------------------------------------
     */
    public string $appName = 'SIB-K MA Persis 31 Banjaran';

    /**
     * --------------------------------------------------------------------------
     * Index File
     * --------------------------------------------------------------------------
     *
     * Typically this will be your index.php file, unless you've renamed it to
     * something else. If you are using mod_rewrite to remove the page set this
     * variable so that it is blank.
     */
    public string $indexPage = '';

    /**
     * --------------------------------------------------------------------------
     * URI PROTOCOL
     * --------------------------------------------------------------------------
     *
     * This item determines which server global should be used to retrieve the
     * URI string.  The default setting of 'REQUEST_URI' works for most servers.
     * If your links do not seem to work, try one of the other delicious flavors:
     *
     * 'REQUEST_URI'    Uses $_SERVER['REQUEST_URI']
     * 'QUERY_STRING'   Uses $_SERVER['QUERY_STRING']
     * 'PATH_INFO'      Uses $_SERVER['PATH_INFO']
     *
     * WARNING: If you set this to 'PATH_INFO', URIs will always be URL-decoded!
     */
    public string $uriProtocol = 'REQUEST_URI';

    /**
     * --------------------------------------------------------------------------
     * Default Locale
     * --------------------------------------------------------------------------
     *
     * The Locale roughly represents the language and location that your visitor
     * is viewing the site from. It affects the language strings and other
     * strings (like currency markers, numbers, etc), that your program
     * should run under for this request.
     */
    public string $defaultLocale = 'id';

    /**
     * --------------------------------------------------------------------------
     * Negotiate Locale
     * --------------------------------------------------------------------------
     *
     * If true, the current Request object will automatically determine the
     * language to use based on the value of the Accept-Language header.
     *
     * If false, no automatic detection will be performed.
     */
    public bool $negotiateLocale = false;

    /**
     * --------------------------------------------------------------------------
     * Supported Locales
     * --------------------------------------------------------------------------
     *
     * If $negotiateLocale is true, this array lists the locales supported
     * by the application in descending order of priority. If no match is
     * found, the first locale will be used.
     */
    public array $supportedLocales = ['id', 'en'];

    /**
     * --------------------------------------------------------------------------
     * Application Timezone
     * --------------------------------------------------------------------------
     *
     * The default timezone that will be used in your application to display
     * dates with the date helper, and can be retrieved through app_timezone()
     */
    public string $appTimezone = 'Asia/Jakarta';

    /**
     * --------------------------------------------------------------------------
     * Default Character Set
     * --------------------------------------------------------------------------
     *
     * This determines which character set is used by default in various methods
     * that require a character set to be provided.
     *
     * See http://php.net/htmlspecialchars for a list of supported charsets.
     */
    public string $charset = 'UTF-8';

    /**
     * --------------------------------------------------------------------------
     * Force Global Secure Requests
     * --------------------------------------------------------------------------
     *
     * If true, this will force every request made to this application to be
     * made via a secure connection (HTTPS). If the incoming request is not
     * secure, the user will be redirected to a secure version of the page
     * and the HTTP Strict Transport Security (HSTS) header will be set.
     */
    public bool $forceGlobalSecureRequests = false;

    /**
     * --------------------------------------------------------------------------
     * Session Driver
     * --------------------------------------------------------------------------
     *
     * The session storage driver to use:
     * - `CodeIgniter\Session\Handlers\FileHandler`
     * - `CodeIgniter\Session\Handlers\DatabaseHandler`
     * - `CodeIgniter\Session\Handlers\MemcachedHandler`
     * - `CodeIgniter\Session\Handlers\RedisHandler`
     */
    public string $sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler';

    /**
     * --------------------------------------------------------------------------
     * Session Cookie Name
     * --------------------------------------------------------------------------
     *
     * The session cookie name, must contain only [0-9a-z_-] characters
     */
    public string $sessionCookieName = 'sibk_session';

    /**
     * --------------------------------------------------------------------------
     * Session Expiration
     * --------------------------------------------------------------------------
     *
     * The number of SECONDS you want the session to last.
     * Setting to 0 (zero) means expire when the browser is closed.
     */
    public int $sessionExpiration = 7200; // 2 hours

    /**
     * --------------------------------------------------------------------------
     * Session Save Path
     * --------------------------------------------------------------------------
     *
     * The location to save sessions to and is driver dependent.
     *
     * For the 'files' driver, it's a path to a writable directory.
     * WARNING: Only absolute paths are supported!
     *
     * For the 'database' driver, it's a table name.
     * Please read up the manual for the format with other session drivers.
     *
     * IMPORTANT: You are REQUIRED to set a valid save path!
     */
    public $sessionSavePath = WRITEPATH . 'session';

    /**
     * --------------------------------------------------------------------------
     * Session Match IP
     * --------------------------------------------------------------------------
     *
     * Whether to match the user's IP address when reading the session data.
     *
     * WARNING: If you're using the database driver, don't forget to update
     *          your session table's PRIMARY KEY when changing this setting.
     */
    public bool $sessionMatchIP = false;

    /**
     * --------------------------------------------------------------------------
     * Session Time to Update
     * --------------------------------------------------------------------------
     *
     * How many seconds between CI regenerating the session ID.
     */
    public int $sessionTimeToUpdate = 300;

    /**
     * --------------------------------------------------------------------------
     * Session Regenerate Destroy
     * --------------------------------------------------------------------------
     *
     * Whether to destroy session data associated with the old session ID
     * when auto-regenerating the session ID. When set to FALSE, the data
     * will be later deleted by the garbage collector.
     */
    public bool $sessionRegenerateDestroy = false;

    /**
     * --------------------------------------------------------------------------
     * Cookie Prefix
     * --------------------------------------------------------------------------
     *
     * Set a cookie name prefix if you need to avoid collisions.
     */
    public string $cookiePrefix = '';

    /**
     * --------------------------------------------------------------------------
     * Cookie Domain
     * --------------------------------------------------------------------------
     *
     * Set to `.your-domain.com` for site-wide cookies.
     */
    public string $cookieDomain = '';

    /**
     * --------------------------------------------------------------------------
     * Cookie Path
     * --------------------------------------------------------------------------
     *
     * Typically will be a forward slash.
     */
    public string $cookiePath = '/';

    /**
     * --------------------------------------------------------------------------
     * Cookie Secure
     * --------------------------------------------------------------------------
     *
     * Cookie will only be set if a secure HTTPS connection exists.
     */
    public bool $cookieSecure = false;

    /**
     * --------------------------------------------------------------------------
     * Cookie HttpOnly
     * --------------------------------------------------------------------------
     *
     * Cookie will only be accessible via HTTP(S) (no JavaScript).
     */
    public bool $cookieHTTPOnly = true;

    /**
     * --------------------------------------------------------------------------
     * Cookie SameSite
     * --------------------------------------------------------------------------
     *
     * Configure cookie SameSite setting. Allowed values are:
     * - None
     * - Lax
     * - Strict
     * - ''
     *
     * Defaults to `Lax` for compatibility with modern browsers. Setting `''`
     * (empty string) means default SameSite attribute set by browsers (`Lax`)
     * will be set on cookies. If set to `None`, `$cookieSecure` must also be set.
     */
    public string $cookieSameSite = 'Lax';

    /**
     * --------------------------------------------------------------------------
     * Reverse Proxy IPs
     * --------------------------------------------------------------------------
     *
     * If your server is behind a reverse proxy, you must whitelist the proxy
     * IP addresses from which CodeIgniter should trust headers such as
     * HTTP_X_FORWARDED_FOR and HTTP_CLIENT_IP in order to properly identify
     * the visitor's IP address.
     *
     * You can use both an array or a comma-separated list of proxy addresses,
     * as well as specifying whole subnets. Here are a few examples:
     *
     * Comma-separated:    '10.0.1.200,192.168.5.0/24'
     * Array: ['10.0.1.200', '192.168.5.0/24']
     */
    public array $proxyIPs = [];

    /**
     * --------------------------------------------------------------------------
     * Allowed Hostnames
     * --------------------------------------------------------------------------
     *
     * If you want to allow only specific hostnames to access your application,
     * you can list them here. By default, all hostnames are allowed.
     *
     * @var list<string>
     */
    public array $allowedHostnames = [];

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * The token name.
     */
    public string $CSRFTokenName = 'csrf_token_sibk';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     *
     * The header name.
     */
    public string $CSRFHeaderName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * The cookie name.
     */
    public string $CSRFCookieName = 'csrf_cookie_sibk';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expire
     * --------------------------------------------------------------------------
     *
     * The number in seconds the token should expire.
     */
    public int $CSRFExpire = 7200;

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Regenerate token on every submission?
     */
    public bool $CSRFRegenerate = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     *
     * Redirect to previous page with error on failure?
     */
    public bool $CSRFRedirect = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF SameSite
     * --------------------------------------------------------------------------
     *
     * Setting for CSRF SameSite cookie token. Allowed values are:
     * - None
     * - Lax
     * - Strict
     * - ''
     */
    public string $CSRFSameSite = 'Lax';

    /**
     * --------------------------------------------------------------------------
     * Content Security Policy
     * --------------------------------------------------------------------------
     *
     * Enables the Response's Content Secure Policy to restrict the sources that
     * can be used for images, scripts, CSS files, audio, video, etc. If enabled,
     * the Response object will populate default values for the policy from the
     * `ContentSecurityPolicy.php` file. Controllers can always add to those
     * restrictions at run time.
     *
     * For a better understanding of CSP, see these documents:
     *
     * @see http://www.w3.org/TR/CSP/
     * @see http://www.html5rocks.com/en/tutorials/security/content-security-policy/
     * @see http://content-security-policy.com/
     * @see https://github.com/paragonie/csp-builder
     */
    public bool $CSPEnabled = false;

    public function __construct()
    {
        parent::__construct();

        // Load configuration from .env
        $this->loadFromEnv();
    }

    /**
     * Load application configuration from environment variables
     */
    private function loadFromEnv(): void
    {
        $this->baseURL                     = env('app.baseURL', $this->baseURL);
        $this->appName                     = env('app.appName', $this->appName);
        $this->indexPage                   = env('app.indexPage', $this->indexPage);
        $this->defaultLocale               = env('app.defaultLocale', $this->defaultLocale);
        $this->appTimezone                 = env('app.appTimezone', $this->appTimezone);
        $this->forceGlobalSecureRequests   = env('app.forceGlobalSecureRequests', $this->forceGlobalSecureRequests);
        $this->CSPEnabled                  = env('app.CSPEnabled', $this->CSPEnabled);

        // Session configuration
        $this->sessionExpiration           = env('session.expiration', $this->sessionExpiration);
        $this->sessionMatchIP              = env('session.matchIP', $this->sessionMatchIP);

        // Cookie configuration
        $this->cookieSecure                = env('cookie.secure', $this->cookieSecure);
        $this->cookieHTTPOnly              = env('cookie.httpOnly', $this->cookieHTTPOnly);
    }
}
