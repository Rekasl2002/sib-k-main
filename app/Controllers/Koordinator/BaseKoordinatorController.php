<?php
namespace App\Controllers\Koordinator;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

abstract class BaseKoordinatorController extends BaseController
{
    /** @var \CodeIgniter\Session\Session */
    protected $session;

    /** @var array|null */
    protected $currentUser = null;

    /** @var string|null */
    protected $roleName = null;

    /**
     * Signature WAJIB sama persis dengan Controller::initController()
     * dan TIDAK boleh return value (void).
     */
    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        // Ambil service session dan hydrate user/role jika ada.
        $this->session = service('session');

        $userId = $this->session->get('user_id');
        if ($userId) {
            $user = (new UserModel())->find($userId);
            if ($user) {
                $this->currentUser = $user;
                $role = (new RoleModel())->find($user['role_id'] ?? null);
                $this->roleName = strtolower($role['role_name'] ?? '');
            }
        }
        // Penting: JANGAN melakukan redirect/return di sini.
        // Validasi role dilakukan lewat requireKoordinator() di setiap action
        // atau lewat RoleFilter pada routes.
    }

    /**
     * Panggil method ini di awal setiap action controller turunan
     * untuk memastikan hanya Koordinator BK yang boleh mengakses.
     * Melempar 404 agar tidak membocorkan keberadaan endpoint.
     */
    protected function requireKoordinator(): void
    {
        if (!$this->session || !$this->session->get('is_logged_in')) {
            // Jika AuthFilter dipasang pada routes, ini praktis tak tercapai.
            // Sebagai fallback: lempar 404.
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->roleName !== 'koordinator bk') {
            throw PageNotFoundException::forPageNotFound();
        }
    }
}
