<?php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Services\ViolationSubmissionService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ViolationSubmissionsController extends BaseController
{
    protected ViolationSubmissionService $service;
    protected StudentModel $studentModel;

    public function __construct()
    {
        helper(['app', 'form', 'url']);

        if (!feature_violation_submissions_enabled()) {
            throw PageNotFoundException::forPageNotFound('Fitur Pengaduan Pelanggaran belum tersedia.');
        }

        $this->service       = new ViolationSubmissionService();
        $this->studentModel  = new StudentModel();
    }

    public function index()
    {
        $uid = $this->currentUserId();
        $rows = $this->service->listForReporter($uid, 'homeroom');

        return view('homeroom_teacher/violation_submissions/index', [
            'title'    => 'Pengaduan Pelanggaran',
            'rows'     => $rows,
            'basePath' => 'homeroom/violation-submissions',
        ]);
    }

    public function create()
    {
        return view('homeroom_teacher/violation_submissions/create', [
            'title'      => 'Tambah Pengaduan Pelanggaran',
            'students'   => $this->fetchStudents(),
            'errors'     => session()->getFlashdata('errors') ?? [],
            'basePath'   => 'homeroom/violation-submissions',
            'row'        => [],
            'mode'       => 'create',
        ]);
    }

    public function store()
    {
        $payload = $this->payload();
        $errors = $this->validatePayload($payload);
        $files  = $this->validEvidenceFiles($errors);

        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $payload['reporter_type']    = 'homeroom';
        $payload['reporter_user_id'] = $this->currentUserId();
        $payload['status']           = 'Diajukan';

        $newId = $this->service->create($payload, $files);
        if ($newId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan pengaduan.');
        }

        return redirect()->to(site_url('homeroom/violation-submissions/show/' . $newId))
            ->with('success', 'Pengaduan berhasil dibuat dan menunggu ditinjau.');
    }

    public function show($id)
    {
        $row = $this->service->getDetailForReporter((int)$id, $this->currentUserId(), 'homeroom');
        if (!$row) {
            return redirect()->to(site_url('homeroom/violation-submissions'))
                ->with('error', 'Data pengaduan tidak ditemukan.');
        }

        return view('homeroom_teacher/violation_submissions/show', [
            'title'      => 'Detail Pengaduan Pelanggaran',
            'row'        => $row,
            'isEditable' => $this->service->isEditable($row),
            'basePath'   => 'homeroom/violation-submissions',
        ]);
    }

    public function edit($id)
    {
        $row = $this->service->getDetailForReporter((int)$id, $this->currentUserId(), 'homeroom');
        if (!$row) {
            return redirect()->to(site_url('homeroom/violation-submissions'))
                ->with('error', 'Data pengaduan tidak ditemukan.');
        }
        if (!$this->service->isEditable($row)) {
            return redirect()->to(site_url('homeroom/violation-submissions/show/' . (int)$id))
                ->with('error', 'Pengaduan ini sudah diproses dan tidak bisa diedit.');
        }

        return view('homeroom_teacher/violation_submissions/edit', [
            'title'      => 'Edit Pengaduan Pelanggaran',
            'row'        => array_merge($row, $this->oldInputSubset()),
            'students'   => $this->fetchStudents(),
            'errors'     => session()->getFlashdata('errors') ?? [],
            'basePath'   => 'homeroom/violation-submissions',
            'mode'       => 'edit',
        ]);
    }

    public function update($id)
    {
        $payload = $this->payload();
        $errors = $this->validatePayload($payload);
        $files  = $this->validEvidenceFiles($errors);
        $remove = $this->request->getPost('remove_evidence');
        $removePaths = is_array($remove) ? $remove : [];

        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $ok = $this->service->updateForReporter((int)$id, $this->currentUserId(), 'homeroom', $payload, $files, $removePaths);
        if (!$ok) {
            return redirect()->to(site_url('homeroom/violation-submissions/show/' . (int)$id))
                ->with('error', 'Gagal memperbarui. Data mungkin sudah diproses.');
        }

        return redirect()->to(site_url('homeroom/violation-submissions/show/' . (int)$id))
            ->with('success', 'Pengaduan berhasil diperbarui.');
    }

    public function delete($id)
    {
        $ok = $this->service->deleteForReporter((int)$id, $this->currentUserId(), 'homeroom');

        return redirect()->to(site_url('homeroom/violation-submissions'))
            ->with($ok ? 'success' : 'error', $ok ? 'Pengaduan berhasil dihapus.' : 'Gagal menghapus pengaduan.');
    }

    protected function currentUserId(): int
    {
        return (int)(session('user_id') ?? 0);
    }

    protected function payload(): array
    {
        $subjectStudentId = $this->request->getPost('subject_student_id');

        return [
            'subject_student_id' => $subjectStudentId !== null && $subjectStudentId !== '' ? (int)$subjectStudentId : null,
            'subject_other_name' => trim((string)$this->request->getPost('subject_other_name')),
            'occurred_date'      => $this->request->getPost('occurred_date') ?: null,
            'occurred_time'      => $this->request->getPost('occurred_time') ?: null,
            'location'           => trim((string)$this->request->getPost('location')),
            'description'        => trim((string)$this->request->getPost('description')),
            'witness'            => trim((string)$this->request->getPost('witness')),
        ];
    }

    protected function validatePayload(array $payload): array
    {
        $errors = [];
        if (empty($payload['subject_student_id']) && $payload['subject_other_name'] === '') {
            $errors['subject'] = 'Terlapor wajib diisi.';
        }
        if (!empty($payload['subject_student_id']) && $payload['subject_other_name'] !== '') {
            $errors['subject'] = 'Pilih salah satu: siswa terdaftar atau nama terlapor lainnya.';
        }
        if (mb_strlen((string)$payload['description']) < 10) {
            $errors['description'] = 'Deskripsi minimal 10 karakter.';
        }
        return $errors;
    }

    protected function validEvidenceFiles(array &$errors): array
    {
        $files = [];
        $uploaded = $this->request->getFiles();
        $candidate = $uploaded['evidence_files'] ?? [];
        $candidate = is_array($candidate) ? $candidate : [$candidate];

        foreach ($candidate as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }
            $ext = strtolower((string)$file->getClientExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                $errors['evidence_files'] = 'Format bukti hanya boleh JPG, JPEG, PNG, atau PDF.';
                break;
            }
            if ((int)$file->getSize() > 3 * 1024 * 1024) {
                $errors['evidence_files'] = 'Ukuran bukti maksimal 3MB per file.';
                break;
            }
            $files[] = $file;
        }

        return $files;
    }

    protected function fetchStudents(): array
    {
        return $this->studentModel
            ->select('students.id, users.full_name, students.nisn, classes.class_name')
            ->join('users', 'users.id = students.user_id', 'left')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.deleted_at', null)
            ->orderBy('classes.class_name', 'ASC')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
    }

    protected function oldInputSubset(): array
    {
        $old = [];
        foreach (['subject_student_id', 'subject_other_name', 'occurred_date', 'occurred_time', 'location', 'description', 'witness'] as $field) {
            $value = $this->request->getOldInput($field);
            if ($value !== null) {
                $old[$field] = $value;
            }
        }
        return $old;
    }
}
