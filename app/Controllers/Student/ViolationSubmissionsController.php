<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Services\ViolationSubmissionService;
use App\Models\StudentModel;
use App\Models\ViolationCategoryModel;

class ViolationSubmissionsController extends BaseController
{
    protected ViolationSubmissionService $service;
    protected StudentModel $studentModel;
    protected ViolationCategoryModel $categoryModel;

    public function __construct()
    {
        $this->service       = new ViolationSubmissionService();
        $this->studentModel  = new StudentModel();
        $this->categoryModel = new ViolationCategoryModel();
    }

    /**
     * Guard sederhana: pastikan user login & role siswa.
     * Lebih tahan banting: cek role_id dan role_name (kalau ada).
     */
    protected function ensureStudent()
    {
        $session = session();

        $uid = (int) ($session->get('user_id') ?? 0);
        if (!$uid) {
            return redirect()->to('/login');
        }

        $rid = (int) ($session->get('role_id') ?? 0);
        $rname = strtolower(trim((string) ($session->get('role_name') ?? '')));

        // Default mapping kamu: 5 = siswa. Tapi kalau role_id berubah, masih aman via role_name.
        $isStudent = ($rid === 5) || in_array($rname, ['siswa', 'student'], true);

        if (!$isStudent) {
            return redirect()->to('/login');
        }

        // (Opsional) permission check kalau helper tersedia:
        // if (function_exists('has_permission') && !has_permission('submit_violation_submissions')) {
        //     return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengakses fitur ini.');
        // }

        return null;
    }

    public function index()
    {
        if ($redir = $this->ensureStudent()) {
            return $redir;
        }

        $uid  = (int) session('user_id');
        $rows = $this->service->listForReporter($uid, 'student');

        return view('student/violation_submissions/index', [
            'title' => 'Pengaduan Pelanggaran',
            'rows'  => $rows,
        ]);
    }

    public function create()
    {
        if ($redir = $this->ensureStudent()) {
            return $redir;
        }

        $uid = (int) session('user_id');

        // Cari student_id dirinya (agar tidak bisa memilih dirinya sebagai terlapor)
        $selfStudent   = $this->studentModel->where('user_id', $uid)->first();
        $selfStudentId = isset($selfStudent['id']) ? (int) $selfStudent['id'] : 0;

        // List siswa terlapor selain dirinya sendiri
        $students = $this->studentModel
            ->select('students.id, users.full_name, students.nis, classes.class_name')
            ->join('users', 'users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('users.deleted_at', null)
            ->where('students.id !=', $selfStudentId ?: 0)
            ->orderBy('classes.class_name', 'ASC')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();

        $categories = $this->categoryModel
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('category_name', 'ASC')
            ->findAll();

        return view('student/violation_submissions/create', [
            'title'      => 'Tambah Pengaduan Pelanggaran',
            'students'   => $students,
            'categories' => $categories,
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function store()
    {
        if ($redir = $this->ensureStudent()) {
            return $redir;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(base_url('student/violation-submissions'));
        }

        $uid = (int) session('user_id');

        // Student_id dirinya (untuk anti “lapor diri sendiri” via POST)
        $selfStudent   = $this->studentModel->where('user_id', $uid)->first();
        $selfStudentId = isset($selfStudent['id']) ? (int) $selfStudent['id'] : 0;

        $subjectStudentIdRaw = $this->request->getPost('subject_student_id');
        $subjectStudentId    = ($subjectStudentIdRaw !== null && $subjectStudentIdRaw !== '')
            ? (int) $subjectStudentIdRaw
            : null;

        $categoryIdRaw = $this->request->getPost('category_id');
        $categoryId    = ($categoryIdRaw !== null && $categoryIdRaw !== '')
            ? (int) $categoryIdRaw
            : null;

        $data = [
            'reporter_type'      => 'student',
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
        ];

        $errors = [];

        // Validasi minimal: terlapor wajib ada (siswa ATAU nama manual)
        if (empty($data['subject_student_id']) && $data['subject_other_name'] === '') {
            $errors['subject'] = 'Terlapor wajib diisi (pilih siswa atau isi nama terlapor).';
        }

        // Cegah lapor dirinya sendiri (meski diakali lewat POST)
        if (!empty($data['subject_student_id']) && $selfStudentId && (int)$data['subject_student_id'] === (int)$selfStudentId) {
            $errors['subject_student_id'] = 'Kamu tidak bisa membuat pengaduan dengan terlapor dirimu sendiri.';
        }

        if (!$this->validate($rules)) {
            $errors = array_merge($errors, $this->validator->getErrors());
        }

        // Validasi file evidence (multi)
        $uploaded      = $this->request->getFiles();
        $evidenceFiles = [];

        if (isset($uploaded['evidence_files'])) {
            $candidate = is_array($uploaded['evidence_files']) ? $uploaded['evidence_files'] : [$uploaded['evidence_files']];

            foreach ($candidate as $f) {
                if (!$f || !$f->isValid() || $f->hasMoved()) {
                    continue;
                }

                $ext = strtolower($f->getClientExtension() ?? '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                    $errors['evidence_files'] = 'Format bukti hanya boleh: JPG, JPEG, PNG, PDF.';
                    break;
                }

                // Maks 3MB per file
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

        return redirect()->to(base_url("student/violation-submissions/show/{$newId}"))
            ->with('success', 'Pengaduan berhasil dibuat dan menunggu ditinjau.');
    }

    public function show($id)
    {
        if ($redir = $this->ensureStudent()) {
            return $redir;
        }

        $uid = (int) session('user_id');

        $row = $this->service->getDetailForReporter((int) $id, $uid, 'student');
        if (!$row) {
            return redirect()->to(base_url('student/violation-submissions'))
                ->with('error', 'Data pengaduan tidak ditemukan.');
        }

        return view('student/violation_submissions/show', [
            'title'      => 'Detail Pengaduan Pelanggaran',
            'row'        => $row,
            'isEditable' => $this->service->isEditable($row),
        ]);
    }

    public function edit($id)
    {
        if ($redir = $this->ensureStudent()) {
            return $redir;
        }

        $uid = (int) session('user_id');
        $row = $this->service->getDetailForReporter((int) $id, $uid, 'student');

        if (!$row) {
            return redirect()->to(base_url('student/violation-submissions'))
                ->with('error', 'Data pengaduan tidak ditemukan.');
        }

        if (!$this->service->isEditable($row)) {
            return redirect()->to(base_url("student/violation-submissions/show/{$id}"))
                ->with('error', 'Pengaduan ini sudah diproses, tidak bisa diedit.');
        }

        // Kalau sebelumnya gagal validasi + redirect->back()->withInput(),
        // view edit kamu pakai $row langsung (bukan old()).
        // Jadi kita timpakan old input ke $row agar form menampilkan input terakhir.
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

        // Student_id dirinya (agar tidak bisa pilih dirinya)
        $selfStudent   = $this->studentModel->where('user_id', $uid)->first();
        $selfStudentId = isset($selfStudent['id']) ? (int) $selfStudent['id'] : 0;

        $students = $this->studentModel
            ->select('students.id, users.full_name, students.nis, classes.class_name')
            ->join('users', 'users.id = students.user_id')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->where('users.deleted_at', null)
            ->where('students.id !=', $selfStudentId ?: 0)
            ->orderBy('classes.class_name', 'ASC')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();

        $categories = $this->categoryModel
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->orderBy('category_name', 'ASC')
            ->findAll();

        return view('student/violation_submissions/edit', [
            'title'      => 'Edit Pengaduan Pelanggaran',
            'row'        => $row,
            'students'   => $students,
            'categories' => $categories,
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function update($id)
    {
        if ($redir = $this->ensureStudent()) {
            return $redir;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(base_url("student/violation-submissions/show/{$id}"));
        }

        $uid = (int) session('user_id');

        // Student_id dirinya (anti lapor diri sendiri via POST)
        $selfStudent   = $this->studentModel->where('user_id', $uid)->first();
        $selfStudentId = isset($selfStudent['id']) ? (int) $selfStudent['id'] : 0;

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
        ];

        $errors = [];

        if (empty($data['subject_student_id']) && $data['subject_other_name'] === '') {
            $errors['subject'] = 'Terlapor wajib diisi (pilih siswa atau isi nama terlapor).';
        }

        if (!empty($data['subject_student_id']) && $selfStudentId && (int)$data['subject_student_id'] === (int)$selfStudentId) {
            $errors['subject_student_id'] = 'Kamu tidak bisa mengubah pengaduan dengan terlapor dirimu sendiri.';
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

                $ext = strtolower($f->getClientExtension() ?? '');
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

        $ok = $this->service->updateForReporter((int) $id, $uid, 'student', $data, $newFiles, $removePaths);

        if (!$ok) {
            return redirect()->to(base_url("student/violation-submissions/show/{$id}"))
                ->with('error', 'Gagal memperbarui. Data mungkin sudah diproses atau tidak ditemukan.');
        }

        return redirect()->to(base_url("student/violation-submissions/show/{$id}"))
            ->with('success', 'Pengaduan berhasil diperbarui.');
    }

    public function delete($id)
    {
        if ($redir = $this->ensureStudent()) {
            return $redir;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(base_url('student/violation-submissions'));
        }

        $uid = (int) session('user_id');

        $ok = $this->service->deleteForReporter((int) $id, $uid, 'student');
        if (!$ok) {
            return redirect()->to(base_url('student/violation-submissions'))
                ->with('error', 'Gagal menghapus. Data mungkin sudah diproses atau tidak ditemukan.');
        }

        return redirect()->to(base_url('student/violation-submissions'))
            ->with('success', 'Pengaduan berhasil dihapus.');
    }
}
