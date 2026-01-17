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
            'title' => 'Login - SIB-K',
            $schoolName = setting('general.school_name', env('school.name')),
            $logo = setting('branding.logo_path', env('school.logo')),
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
            'username' => 'required',
            'password' => 'required',
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
            'title' => 'Registrasi - SIB-K',
            $schoolName = setting('general.school_name', env('school.name')),
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
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]|alpha_numeric',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]',
            'full_name' => 'required|min_length[3]|max_length[255]',
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
            'email' => $this->request->getPost('email'),
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
            'title' => 'Lupa Password - SIB-K',
            $schoolName = setting('general.school_name', env('school.name')),
        ];

        return view('auth/forgot_password', $data);
    }

    /**
     * Send password reset link
     * 
     * @return RedirectResponse
     */
    public function sendResetLink()
    {
        $rules = [
            'email' => 'required|valid_email',
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
            return redirect()->back()
                ->withInput()
                ->with('error', 'Email tidak ditemukan dalam sistem.');
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

        // Send email (implement email service)
        // For now, just show success message
        return redirect()->to('/login')
            ->with('success', 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau spam folder.');
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
            'name'   => 'sibk_remember',
            'value'  => $token,
            'expire' => 2592000, // 30 days
            'secure' => false,
            'httponly' => true,
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
