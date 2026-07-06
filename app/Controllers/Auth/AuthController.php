<?php

/**
 * File Path: app/Controllers/Auth/AuthController.php
 * 
 * Authentication Controller
 * Menangani proses authentication (login, logout, register)
 * 
 * @package    SIB-K
 * @subpackage Controllers
 * @category   Authentication
 * @author     Development Team
 * @created    2025-01-01
 */

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Libraries\AuthLibrary;
use App\Services\PasswordResetRequestService;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;


class AuthController extends BaseController
{
    protected $authLib;
    protected $userModel;

    public function __construct()
    {
        $this->authLib = new AuthLibrary();
        $this->userModel = new UserModel();
    }

    /**
     * Display login page
     * 
     * @return string|RedirectResponse
     */
    public function index()
    {
        // If already logged in, redirect to dashboard
        if ($this->authLib->isLoggedIn()) {
            return redirect()->to($this->authLib->getRedirectPath());
        }

        $data = [
            'title'      => 'Login - SIB-K',
            'schoolName' => setting('general.school_name', env('school.name')),
            'logo'       => setting('branding.logo_path', env('school.logo')),
        ];

        return view('auth/login', $data);
    }

    /**
     * Process login
     * 
     * @return RedirectResponse
     */
    public function login()
    {
        // Validation rules
        $rules = [
            'username' => ['label' => 'Username atau Email', 'rules' => 'required'],
            'password' => ['label' => 'Password', 'rules' => 'required'],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember');

        // Attempt login
        if ($this->authLib->login($username, $password)) {
            // Set remember me cookie if checked
            if ($remember) {
                $this->setRememberMeCookie($username);
            }

            // Check for redirect URL
            $redirectUrl = session('redirect_url');
            if ($redirectUrl) {
                session()->remove('redirect_url');
                return redirect()->to($redirectUrl);
            }

            // Redirect to appropriate dashboard
            return redirect()->to($this->authLib->getRedirectPath())
                ->with('success', 'Selamat datang, ' . $this->authLib->user()['full_name'] . '!');
        }

        // Login failed
        return redirect()->back()
            ->withInput()
            ->with('error', 'Username atau password salah. Silakan coba lagi.');
    }

    /**
     * Process logout
     * 
     * @return RedirectResponse
     */
    public function logout()
    {
        $this->authLib->logout();

        // Remove remember me cookie
        $this->removeRememberMeCookie();

        return redirect()->to('/login')
            ->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Display registration page
     * 
     * @return string|RedirectResponse
     */
    public function register()
    {
        // If already logged in, redirect to dashboard
        if ($this->authLib->isLoggedIn()) {
            return redirect()->to($this->authLib->getRedirectPath());
        }

        // Check if registration is enabled
        if (!env('feature.registration', false)) {
            return redirect()->to('/login')
                ->with('error', 'Pendaftaran saat ini tidak tersedia.');
        }

        $data = [
            'title'      => 'Registrasi - SIB-K',
            'schoolName' => setting('general.school_name', env('school.name')),
        ];

        return view('auth/register', $data);
    }

    /**
     * Process registration
     * 
     * @return RedirectResponse
     */
    public function doRegister()
    {
        // Check if registration is enabled
        if (!env('feature.registration', false)) {
            return redirect()->to('/login')
                ->with('error', 'Pendaftaran saat ini tidak tersedia.');
        }

        // Validation rules
        $rules = [
            'username' => ['label' => 'Username', 'rules' => 'required|min_length[3]|max_length[50]|is_unique[users.username]|alpha_numeric'],
            'email' => ['label' => 'Email', 'rules' => 'permit_empty|valid_email|is_unique[users.email]'],
            'password' => ['label' => 'Password', 'rules' => 'required|min_length[6]'],
            'password_confirm' => [
                'label' => 'Konfirmasi Password',
                'rules' => 'required|matches[password]',
                'errors' => ['matches' => 'Konfirmasi Password tidak sama dengan Password.'],
            ],
            'full_name' => ['label' => 'Nama Lengkap', 'rules' => 'required|min_length[3]|max_length[255]'],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Prepare user data
        $userData = [
            'role_id' => 5, // Default: Siswa
            'username' => $this->request->getPost('username'),
            'email' => trim((string) $this->request->getPost('email')) ?: null,
            'password' => $this->request->getPost('password'),
            'full_name' => $this->request->getPost('full_name'),
            'phone' => $this->request->getPost('phone'),
            'is_active' => 0, // Inactive until admin approval
        ];

        // Insert user
        if ($this->userModel->insert($userData)) {
            return redirect()->to('/login')
                ->with('success', 'Registrasi berhasil! Silakan tunggu konfirmasi admin untuk mengaktifkan akun Anda.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Registrasi gagal. Silakan coba lagi.');
    }

    /**
     * Display forgot password page
     * 
     * @return string|RedirectResponse
     */
    public function forgotPassword()
    {
        // If already logged in, redirect to dashboard
        if ($this->authLib->isLoggedIn()) {
            return redirect()->to($this->authLib->getRedirectPath());
        }

        $data = [
            'title'      => 'Lupa Password - SIB-K',
            'schoolName' => setting('general.school_name', env('school.name')),
        ];

        return view('auth/forgot_password', $data);
    }

    /**
     * Process forgot password request.
     *
     * Default mode creates an Admin follow-up request. SMTP mode can send a reset link later.
     */
    public function sendResetLink()
    {
        // Jalur reset berbasis token email (SMTP/Gmail) sengaja DINONAKTIFKAN.
        // Verifikasi via email belum menjadi bagian pengembangan; method
        // sendEmailResetLink() & passwordResetMode() dipertahankan sebagai
        // cadangan bila kelak SMTP diaktifkan. Untuk saat ini semua permintaan
        // lupa password diteruskan ke Admin (email + nomor telepon).
        return $this->sendAdminResetRequest();
    }

    private function sendAdminResetRequest(): RedirectResponse
    {
        $rules = [
            'email' => ['label' => 'Email', 'rules' => 'permit_empty|valid_email|max_length[255]'],
            'phone' => ['label' => 'Nomor Telepon', 'rules' => 'permit_empty|max_length[30]'],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = trim((string) $this->request->getPost('email'));
        $phone = $this->normalizePhone((string) $this->request->getPost('phone'));

        if ($email === '' && $phone === '') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Isi email atau nomor telepon yang dapat dihubungi.');
        }

        $user = $this->findUserByResetContact($email, $phone);

        (new PasswordResetRequestService())->createAdminRequest(
            $email !== '' ? $email : null,
            $phone !== '' ? $phone : null,
            $user,
            $this->request
        );

        return redirect()->to('/login')
            ->with('success', 'Permintaan reset password telah diteruskan ke Admin. Silakan tunggu konfirmasi dari pihak sekolah.');
    }

    private function sendEmailResetLink(): RedirectResponse
    {
        $rules = [
            'email' => ['label' => 'Email', 'rules' => 'required|valid_email'],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');

        // Check if email exists
        $user = $this->userModel->where('email', $email)->first();

        if (!$user) {
            return redirect()->to('/login')
                ->with('success', 'Jika email terdaftar, instruksi reset password akan dikirim.');
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to database (you need to create password_resets table)
        $db = \Config\Database::connect();
        $db->table('password_resets')->insert([
            'email' => $email,
            'token' => password_hash($token, PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiry,
        ]);

        $resetUrl = site_url('reset-password/' . $token);

        $sent = (new PasswordResetRequestService())->sendResetLinkEmail($user, $resetUrl);
        if ($sent) {
            return redirect()->to('/login')
                ->with('success', 'Jika email terdaftar, instruksi reset password akan dikirim.');
        }

        $redirect = redirect()->to('/login')
            ->with('success', 'Permintaan reset password diproses. Jika pengiriman email belum aktif, hubungi Admin sekolah.');

        if ($this->envBool('password_reset.showLocalLink', false)) {
            $redirect->with('reset_link', $resetUrl);
        }

        return $redirect;
    }

    /**
     * Display reset password page
     *
     * @param string $token
     * @return string|RedirectResponse
     */
    public function resetPassword(string $token)
    {
        if ($this->authLib->isLoggedIn()) {
            return redirect()->to($this->authLib->getRedirectPath());
        }

        $reset = $this->findValidPasswordReset($token);
        if (!$reset) {
            return redirect()->to('/forgot-password')
                ->with('error', 'Token reset password tidak valid atau sudah kedaluwarsa.');
        }

        return view('auth/reset_password', [
            'title'      => 'Reset Password - SIB-K',
            'schoolName' => setting('general.school_name', env('school.name')),
            'token'      => $token,
            'email'      => $reset['email'],
        ]);
    }

    /**
     * Process password reset
     *
     * @return RedirectResponse
     */
    public function doResetPassword(): RedirectResponse
    {
        $rules = [
            'token'            => ['label' => 'Token', 'rules' => 'required'],
            'password'         => ['label' => 'Password Baru', 'rules' => 'required|min_length[6]'],
            'password_confirm' => [
                'label' => 'Konfirmasi Password',
                'rules' => 'required|matches[password]',
                'errors' => ['matches' => 'Konfirmasi Password tidak sama dengan Password Baru.'],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $token = (string) $this->request->getPost('token');
        $reset = $this->findValidPasswordReset($token);
        if (!$reset) {
            return redirect()->to('/forgot-password')
                ->with('error', 'Token reset password tidak valid atau sudah kedaluwarsa.');
        }

        $user = $this->userModel
            ->where('email', $reset['email'])
            ->where('deleted_at', null)
            ->first();

        if (!$user) {
            return redirect()->to('/forgot-password')
                ->with('error', 'Akun untuk token reset password tidak ditemukan.');
        }

        $ok = $this->userModel->update((int) $user['id'], [
            'password' => (string) $this->request->getPost('password'),
        ]);

        if (!$ok) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui password. Silakan coba lagi.');
        }

        $db = \Config\Database::connect();
        $db->table('password_resets')
            ->where('email', $reset['email'])
            ->delete();

        return redirect()->to('/login')
            ->with('success', 'Password berhasil diperbarui. Silakan login dengan password baru.');
    }

    /**
     * Cari token reset password yang masih berlaku.
     */
    private function findValidPasswordReset(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('password_resets')) {
            return null;
        }

        $rows = $db->table('password_resets')
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            if (!empty($row['token']) && password_verify($token, (string) $row['token'])) {
                return $row;
            }
        }

        return null;
    }

    private function findUserByResetContact(string $email, string $phone): ?array
    {
        if ($email === '' && $phone === '') {
            return null;
        }

        $builder = $this->userModel
            ->where('deleted_at', null)
            ->groupStart();

        if ($email !== '') {
            $builder->where('email', $email);
        }

        if ($phone !== '') {
            $builder->orWhere('phone', $phone);
            $digitsOnly = preg_replace('/\D+/', '', $phone);
            if ($digitsOnly && $digitsOnly !== $phone) {
                $builder->orWhere('phone', $digitsOnly);
            }
        }

        return $builder->groupEnd()->first();
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';

        return substr($phone, 0, 30);
    }

    private function passwordResetMode(): string
    {
        $mode = strtolower(trim((string) env('password_reset.mode', 'admin_request')));

        return in_array($mode, ['admin_request', 'smtp_link'], true) ? $mode : 'admin_request';
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = env($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Set remember me cookie
     * 
     * @param string $username
     * @return void
     */
    private function setRememberMeCookie($username)
    {
        $token = bin2hex(random_bytes(32));

        // Save token to database or session
        session()->set('remember_token', $token);

        // Set cookie for 30 days
        set_cookie([
            'name'     => 'sibk_remember',
            'value'    => $token,
            'expire'   => 2592000, // 30 days
            'secure'   => $this->request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Remove remember me cookie
     * 
     * @return void
     */
    private function removeRememberMeCookie()
    {
        delete_cookie('sibk_remember');
        session()->remove('remember_token');
    }

    /**
     * Verify account
     * 
     * @param string $token
     * @return RedirectResponse
     */
    public function verify($token)
    {
        // Verify email token
        $db = \Config\Database::connect();
        $verification = $db->table('email_verifications')
            ->where('token', $token)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getRowArray();

        if (!$verification) {
            return redirect()->to('/login')
                ->with('error', 'Token verifikasi tidak valid atau sudah kadaluarsa.');
        }

        // Update user status
        $this->userModel->update($verification['user_id'], ['is_active' => 1]);

        // Delete verification token
        $db->table('email_verifications')->where('token', $token)->delete();

        return redirect()->to('/login')
            ->with('success', 'Email Anda telah berhasil diverifikasi. Silakan login.');
    }
}
