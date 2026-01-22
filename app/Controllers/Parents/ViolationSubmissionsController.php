<?php
// app/Controllers/Parents/ViolationSubmissionsController.php

namespace App\Controllers\Parents;

use App\Controllers\BaseController;
use App\Services\ViolationSubmissionService;
use App\Models\StudentModel;
use App\Models\ViolationCategoryModel;

class ViolationSubmissionsController extends BaseController
{
    protected ViolationSubmissionService $service;
    protected StudentModel $studentModel;
    protected ViolationCategoryModel $categoryModel;
    protected $db;

    public function __construct()
    {
        $this->service       = new ViolationSubmissionService();
        $this->studentModel  = new StudentModel();
        $this->categoryModel = new ViolationCategoryModel();
        $this->db            = \Config\Database::connect();
    }

    /**
     * Guard sederhana: pastikan user login & role orang tua (kalau role_name tersedia).
     * Dibuat toleran supaya tidak “ngeblok” jika session role_name kosong (mis. sistem pakai filter routes).
     */
    protected function ensureParent()
    {
        $session = session();

        $uid = (int) ($session->get('user_id') ?? 0);
        if (!$uid) {
            return redirect()->to('/login');
        }

        $rname = strtolower(trim((string) ($session->get('role_name') ?? '')));

        // Kalau role_name ada, pastikan ini orang tua.
        if ($rname !== '') {
            $isParent =
                in_array($rname, ['orang tua', 'orangtua', 'parent', 'wali', 'wali murid', 'walimurid', 'parents'], true)
                || (strpos($rname, 'parent') !== false)
                || (strpos($rname, 'orang') !== false)
                || (strpos($rname, 'wali') !== false);

            if (!$isParent) {
                return redirect()->to('/login');
            }
        }

        // (Opsional) permission check kalau helper tersedia:
        // if (function_exists('has_permission') && !has_permission('submit_violation_submissions')) {
        //     return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengakses fitur ini.');
        // }

        return null;
    }

    public function index()
    {
        if ($redir = $this->ensureParent()) {
            return $redir;
        }

        $uid  = (int) session('user_id');
        $rows = $this->service->listForReporter($uid, 'parent');

        return view('parent/violation_submissions/index', [
            'title' => 'Pengaduan Pelanggaran',
            'rows'  => $rows,
        ]);
    }

    public function create()
    {
        if ($redir = $this->ensureParent()) {
            return $redir;
        }

        return view('parent/violation_submissions/create', [
            'title'      => 'Tambah Pengaduan Pelanggaran',
            'students'   => $this->fetchStudentsForDropdown(),
            'categories' => $this->fetchCategories(),
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function store()
    {
        if ($redir = $this->ensureParent()) {
            return $redir;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(base_url('parent/violation-submissions'));
        }

        $uid = (int) session('user_id');

        $subjectStudentIdRaw = $this->request->getPost('subject_student_id');
        $subjectStudentId    = ($subjectStudentIdRaw !== null && $subjectStudentIdRaw !== '')
            ? (int) $subjectStudentIdRaw
            : null;

        $categoryIdRaw = $this->request->getPost('category_id');
        $categoryId    = ($categoryIdRaw !== null && $categoryIdRaw !== '')
            ? (int) $categoryIdRaw
            : null;

        $data = [
            'reporter_type'      => 'parent',
            'reporter_user_id'   => $uid,
            'subject_student_id' => $subjectStudentId,
            'subject_other_name' => trim((string) $this->request->getPost('subject_other_name')),
            'category_id'        => $categoryId,
            'occurred_date'      => $this->request->getPost('occurred_date') ?: null,
            'occurred_time'      => $this->request->getPost('occurred_time') ?: null,
            'location'           => trim((string) $this->request->getPost('location')),
            'description'        => trim((string) $this->request->getPost('description')),
            'witness'            => trim((string) $this->request->getPost('witness')),
            'status'             => 'Diajukan',
        ];

        $rules = [
            'description'        => 'required|min_length[10]',
            'location'           => 'permit_empty|max_length[255]',
            'witness'            => 'permit_empty|max_length[255]',
            'subject_other_name' => 'permit_empty|max_length[255]',
            'subject_student_id' => 'permit_empty|is_natural_no_zero',
            'category_id'        => 'permit_empty|is_natural_no_zero',
            'occurred_date'      => 'permit_empty|valid_date[Y-m-d]',
            'occurred_time'      => 'permit_empty',
        ];

        $errors = [];

        // Validasi: terlapor wajib ada (siswa ATAU nama manual), tapi tidak boleh dua-duanya
        $hasStudent = !empty($data['subject_student_id']);
        $hasOther   = $data['subject_other_name'] !== '';
        if (!$hasStudent && !$hasOther) {
            $errors['subject'] = 'Terlapor wajib diisi (pilih siswa atau isi nama terlapor).';
        }
        if ($hasStudent && $hasOther) {
            $errors['subject'] = 'Pilih salah satu saja: terlapor siswa ATAU nama terlapor (lainnya).';
        }

        if (!$this->validate($rules)) {
            $errors = array_merge($errors, $this->validator->getErrors());
        }

        // Validasi file evidence (multi) – samakan dengan versi siswa
        $uploaded      = $this->request->getFiles();
        $evidenceFiles = [];

        if (isset($uploaded['evidence_files'])) {
            $candidate = is_array($uploaded['evidence_files']) ? $uploaded['evidence_files'] : [$uploaded['evidence_files']];

            foreach ($candidate as $f) {
                if (!$f || !$f->isValid() || $f->hasMoved()) {
                    continue;
                }

                $ext = strtolower((string) ($f->getClientExtension() ?? ''));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                    $errors['evidence_files'] = 'Format bukti hanya boleh: JPG, JPEG, PNG, PDF.';
                    break;
                }

                if (($f->getSize() ?? 0) > 3 * 1024 * 1024) {
                    $errors['evidence_files'] = 'Ukuran bukti maksimal 3MB per file.';
                    break;
                }

                $evidenceFiles[] = $f;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $errors);
        }

        $newId = (int) $this->service->create($data, $evidenceFiles);

        if ($newId <= 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan pengaduan. Silakan coba lagi.');
        }

        return redirect()->to(base_url("parent/violation-submissions/show/{$newId}"))
            ->with('success', 'Pengaduan berhasil dibuat dan menunggu ditinjau.');
    }

    public function show($id)
    {
        if ($redir = $this->ensureParent()) {
            return $redir;
        }

        $uid = (int) session('user_id');

        $row = $this->service->getDetailForReporter((int) $id, $uid, 'parent');
        if (!$row) {
            return redirect()->to(base_url('parent/violation-submissions'))
                ->with('error', 'Data pengaduan tidak ditemukan.');
        }

        // Biarkan view memakai status/badge juga, tapi kita kirim untuk konsistensi
        $status = (string) ($row['status'] ?? 'Diajukan');
        $badge  = $this->statusBadge($status);

        return view('parent/violation_submissions/show', [
            'title'      => 'Detail Pengaduan Pelanggaran',
            'row'        => $row,
            'status'     => $status,
            'badge'      => $badge,
            'isEditable' => $this->service->isEditable($row),
        ]);
    }

    public function edit($id)
    {
        if ($redir = $this->ensureParent()) {
            return $redir;
        }

        $uid = (int) session('user_id');
        $row = $this->service->getDetailForReporter((int) $id, $uid, 'parent');

        if (!$row) {
            return redirect()->to(base_url('parent/violation-submissions'))
                ->with('error', 'Data pengaduan tidak ditemukan.');
        }

        if (!$this->service->isEditable($row)) {
            return redirect()->to(base_url("parent/violation-submissions/show/{$id}"))
                ->with('error', 'Pengaduan ini sudah diproses, tidak bisa diedit.');
        }

        // Timpakan old input ke $row (biar edit menampilkan input terakhir kalau gagal validasi)
        $oldKeys = [
            'subject_student_id', 'subject_other_name', 'category_id',
            'occurred_date', 'occurred_time', 'location', 'description', 'witness'
        ];
        foreach ($oldKeys as $k) {
            $oldVal = $this->request->getOldInput($k);
            if ($oldVal !== null) {
                $row[$k] = $oldVal;
            }
        }

        return view('parent/violation_submissions/edit', [
            'title'      => 'Edit Pengaduan Pelanggaran',
            'row'        => $row,
            'students'   => $this->fetchStudentsForDropdown(),
            'categories' => $this->fetchCategories(),
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function update($id)
    {
        if ($redir = $this->ensureParent()) {
            return $redir;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(base_url("parent/violation-submissions/show/{$id}"));
        }

        $uid = (int) session('user_id');

        $row = $this->service->getDetailForReporter((int) $id, $uid, 'parent');
        if (!$row) {
            return redirect()->to(base_url('parent/violation-submissions'))
                ->with('error', 'Data pengaduan tidak ditemukan.');
        }

        if (!$this->service->isEditable($row)) {
            return redirect()->to(base_url("parent/violation-submissions/show/{$id}"))
                ->with('error', 'Pengaduan ini sudah diproses, tidak bisa disimpan.');
        }

        $subjectStudentIdRaw = $this->request->getPost('subject_student_id');
        $subjectStudentId    = ($subjectStudentIdRaw !== null && $subjectStudentIdRaw !== '')
            ? (int) $subjectStudentIdRaw
            : null;

        $categoryIdRaw = $this->request->getPost('category_id');
        $categoryId    = ($categoryIdRaw !== null && $categoryIdRaw !== '')
            ? (int) $categoryIdRaw
            : null;

        $data = [
            'subject_student_id' => $subjectStudentId,
            'subject_other_name' => trim((string) $this->request->getPost('subject_other_name')),
            'category_id'        => $categoryId,
            'occurred_date'      => $this->request->getPost('occurred_date') ?: null,
            'occurred_time'      => $this->request->getPost('occurred_time') ?: null,
            'location'           => trim((string) $this->request->getPost('location')),
            'description'        => trim((string) $this->request->getPost('description')),
            'witness'            => trim((string) $this->request->getPost('witness')),
        ];

        $rules = [
            'description'        => 'required|min_length[10]',
            'location'           => 'permit_empty|max_length[255]',
            'witness'            => 'permit_empty|max_length[255]',
            'subject_other_name' => 'permit_empty|max_length[255]',
            'subject_student_id' => 'permit_empty|is_natural_no_zero',
            'category_id'        => 'permit_empty|is_natural_no_zero',
            'occurred_date'      => 'permit_empty|valid_date[Y-m-d]',
            'occurred_time'      => 'permit_empty',
        ];

        $errors = [];

        $hasStudent = !empty($data['subject_student_id']);
        $hasOther   = $data['subject_other_name'] !== '';
        if (!$hasStudent && !$hasOther) {
            $errors['subject'] = 'Terlapor wajib diisi (pilih siswa atau isi nama terlapor).';
        }
        if ($hasStudent && $hasOther) {
            $errors['subject'] = 'Pilih salah satu saja: terlapor siswa ATAU nama terlapor (lainnya).';
        }

        if (!$this->validate($rules)) {
            $errors = array_merge($errors, $this->validator->getErrors());
        }

        $remove = $this->request->getPost('remove_evidence');
        $removePaths = is_array($remove) ? $remove : [];

        // Validasi file evidence baru (multi)
        $uploaded = $this->request->getFiles();
        $newFiles = [];

        if (isset($uploaded['evidence_files'])) {
            $candidate = is_array($uploaded['evidence_files']) ? $uploaded['evidence_files'] : [$uploaded['evidence_files']];

            foreach ($candidate as $f) {
                if (!$f || !$f->isValid() || $f->hasMoved()) {
                    continue;
                }

                $ext = strtolower((string) ($f->getClientExtension() ?? ''));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                    $errors['evidence_files'] = 'Format bukti hanya boleh: JPG, JPEG, PNG, PDF.';
                    break;
                }

                if (($f->getSize() ?? 0) > 3 * 1024 * 1024) {
                    $errors['evidence_files'] = 'Ukuran bukti maksimal 3MB per file.';
                    break;
                }

                $newFiles[] = $f;
            }
        }

        if (!empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $errors);
        }

        $ok = $this->service->updateForReporter((int) $id, $uid, 'parent', $data, $newFiles, $removePaths);

        if (!$ok) {
            return redirect()->to(base_url("parent/violation-submissions/show/{$id}"))
                ->with('error', 'Gagal memperbarui. Data mungkin sudah diproses atau tidak ditemukan.');
        }

        return redirect()->to(base_url("parent/violation-submissions/show/{$id}"))
            ->with('success', 'Pengaduan berhasil diperbarui.');
    }

    public function delete($id)
    {
        if ($redir = $this->ensureParent()) {
            return $redir;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(base_url('parent/violation-submissions'));
        }

        $uid = (int) session('user_id');

        $ok = $this->service->deleteForReporter((int) $id, $uid, 'parent');
        if (!$ok) {
            return redirect()->to(base_url('parent/violation-submissions'))
                ->with('error', 'Gagal menghapus. Data mungkin sudah diproses atau tidak ditemukan.');
        }

        return redirect()->to(base_url('parent/violation-submissions'))
            ->with('success', 'Pengaduan berhasil dihapus.');
    }

    /**
     * Ambil opsi siswa untuk dropdown (tahan banting: coba join users+classes dulu, fallback ke students).
     * Output keys disamakan dengan yang dipakai view: id, full_name, nis, class_name
     */
    private function fetchStudentsForDropdown(): array
    {
        // Coba pola yang sama dengan versi siswa (users.full_name)
        try {
            $b = $this->db->table('students')
                ->select('students.id, users.full_name, students.nis, classes.class_name')
                ->join('users', 'users.id = students.user_id')
                ->join('classes', 'classes.id = students.class_id', 'left')
                ->orderBy('classes.class_name', 'ASC')
                ->orderBy('users.full_name', 'ASC');

            // Soft delete guard kalau kolom ada
            try {
                $studentFields = $this->db->getFieldNames('students') ?? [];
                if (in_array('deleted_at', $studentFields, true)) {
                    $b->where('students.deleted_at', null);
                }
            } catch (\Throwable $e) {}

            try {
                $userFields = $this->db->getFieldNames('users') ?? [];
                if (in_array('deleted_at', $userFields, true)) {
                    $b->where('users.deleted_at', null);
                }
            } catch (\Throwable $e) {}

            $rows = $b->get()->getResultArray();
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            // Fallback: kalau schema students punya full_name langsung
            try {
                $b = $this->db->table('students')
                    ->select('id, full_name, nis')
                    ->orderBy('full_name', 'ASC');

                $rows = $b->get()->getResultArray();
                $rows = is_array($rows) ? $rows : [];

                // Tambahkan class_name kosong agar view aman
                foreach ($rows as &$r) {
                    if (!isset($r['class_name'])) $r['class_name'] = null;
                }
                return $rows;
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    /**
     * Ambil kategori (sebaiknya hanya aktif, kalau kolom is_active ada).
     */
    private function fetchCategories(): array
    {
        try {
            $b = $this->db->table('violation_categories')
                ->select('id, category_name')
                ->orderBy('category_name', 'ASC');

            // Soft delete guard kalau kolom ada
            try {
                $fields = $this->db->getFieldNames('violation_categories') ?? [];
                if (in_array('deleted_at', $fields, true)) {
                    $b->where('deleted_at', null);
                }
                if (in_array('is_active', $fields, true)) {
                    $b->where('is_active', 1);
                }
            } catch (\Throwable $e) {}

            $rows = $b->get()->getResultArray();
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            // Fallback via model
            try {
                $rows = $this->categoryModel->orderBy('category_name', 'ASC')->findAll();
                return is_array($rows) ? $rows : [];
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    private function statusBadge(string $status): string
    {
        $s = strtolower(trim($status));
        return match ($s) {
            'diajukan'   => 'warning',
            'ditinjau'   => 'info',
            'ditolak'    => 'danger',
            'diterima'   => 'success',
            'dikonversi' => 'primary',
            default      => 'secondary',
        };
    }
}
