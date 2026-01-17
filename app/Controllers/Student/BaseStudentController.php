<?php
// app/Controllers/Student/BaseStudentController.php
namespace App\Controllers\Student;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseStudentController extends BaseController
{
    protected BaseConnection $db;
    protected ?int $userId = null;
    protected ?int $studentId = null;
    protected string $tz = 'Asia/Jakarta';

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        // Koneksi DB
        $this->db = \Config\Database::connect();

        // Bootstrap identitas
        $this->bootstrapIdentity();
    }

    /**
     * Ambil user_id & student_id dari session; jika student_id belum ada, cari di DB lalu cache.
     */
    protected function bootstrapIdentity(): void
    {
        $sess = session();

        $this->userId = (int) ($sess->get('user_id') ?? 0);

        if ($this->userId > 0) {
            // Pakai cache session bila tersedia
            $this->studentId = (int) ($sess->get('student_id') ?? 0);

            if ($this->studentId === 0) {
                $row = $this->db->table('students')
                    ->select('id')
                    ->where('user_id', $this->userId)
                    ->get()
                    ->getRow();
                $this->studentId = (int) ($row->id ?? 0);

                if ($this->studentId > 0) {
                    $sess->set('student_id', $this->studentId);
                }
            }
        }
    }

    /**
     * Guard khusus area Siswa: pastikan login, role Siswa/Student, dan punya record students.
     * Menghindari loop/404 dengan redirect ke dashboard sesuai role saat tidak cocok.
     * Stop eksekusi menggunakan send()+exit agar controller turunan tak perlu return manual.
     */
    protected function requireStudent(): void
    {
        $sess = session();

        // Belum login -> ke login
        if (!$this->userId) {
            $this->redirectAndExit(route_to('login'));
        }

        // Jika role bukan Siswa/Student -> arahkan ke dashboard milik rolenya
        $role = (string) ($sess->get('role_name') ?? '');
        if ($role && !$this->isStudentRole($role)) {
            $this->redirectAndExit($this->roleRedirectPath($role), 'Akses area Siswa ditolak.');
        }

        // Role Siswa tetapi belum punya record students -> arahkan ke profil umum (hindari loop /student/*)
        if (!$this->studentId) {
            $this->redirectAndExit(route_to('profile'), 'Akun Anda belum ditautkan ke data Siswa, hubungi admin.');
        }
    }

    /** True jika nama role termasuk kategori siswa (case-insensitive, dukung sinonim) */
    protected function isStudentRole(string $role): bool
    {
        $r = strtolower(trim($role));
        return in_array($r, ['siswa', 'student'], true);
    }

    /**
     * Pemetaan dashboard default per role (case-insensitive + sinonim umum).
     */
    protected function roleRedirectPath(string $role): string
    {
        $r = strtolower(trim($role));
        $map = [
            'admin'          => '/admin/dashboard',
            'koordinator bk' => '/koordinator/dashboard',
            'koordinator'    => '/koordinator/dashboard',
            'guru bk'        => '/counselor/dashboard',
            'counselor'      => '/counselor/dashboard',
            'wali kelas'     => '/homeroom/dashboard',
            'homeroom'       => '/homeroom/dashboard',
            'orang tua'      => '/parent/dashboard',
            'parent'         => '/parent/dashboard',
            'siswa'          => '/student/dashboard',
            'student'        => '/student/dashboard',
        ];
        return $map[$r] ?? '/';
    }

    /**
     * Helper redirect yang langsung mengirim response dan menghentikan eksekusi.
     */
    protected function redirectAndExit(string $to, ?string $flashError = null): void
    {
        if ($flashError) {
            session()->setFlashdata('error', $flashError);
        }
        redirect()->to($to)->send();
        exit;
    }

    /**
     * Helper waktu sekarang sesuai timezone aplikasi.
     */
    protected function now(): string
    {
        return Time::now($this->tz)->toDateTimeString();
    }

    /**
     * Ambil objek siswa aktif (opsional untuk turunan).
     */
    protected function currentStudent(): ?object
    {
        if (!$this->studentId) {
            return null;
        }
        return $this->db->table('students')->where('id', $this->studentId)->get()->getRow();
    }

    /**
     * Ambil meta siswa + normalisasi grade dalam dua format:
     *  - grade_level_roman: X/XI/XII
     *  - grade_level_num  : 10/11/12
     *
     * Mengembalikan array agar mudah dipakai di query builder controller turunan.
     */
    protected function getStudentMeta(): array
    {
        $row = $this->db->table('students s')
            ->select('s.id as student_id, s.class_id, c.class_name, c.major, c.grade_level')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->where('s.id', $this->studentId)
            ->get()
            ->getRowArray() ?? [];

        $roman = strtoupper(trim((string)($row['grade_level'] ?? '')));
        $mapRomanToNum = ['X' => '10', 'XI' => '11', 'XII' => '12'];
        $mapNumToRoman = ['10' => 'X', '11' => 'XI', '12' => 'XII'];

        // Deteksi angka bila grade_level sudah angka
        $num = null;
        if (isset($mapRomanToNum[$roman])) {
            $num = $mapRomanToNum[$roman];
        } else {
            $maybeNum = trim((string)($row['grade_level'] ?? ''));
            if (in_array($maybeNum, ['10', '11', '12'], true)) {
                $num = $maybeNum;
                $roman = $mapNumToRoman[$num];
            } else {
                // Tidak dikenali, biarkan null agar controller bisa fallback
                $roman = $roman ?: null;
                $num   = null;
            }
        }

        $row['grade_level_roman'] = $roman ?: null;
        $row['grade_level_num']   = $num ?: null;

        return $row;
    }
}
