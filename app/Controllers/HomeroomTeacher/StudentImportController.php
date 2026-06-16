<?php

namespace App\Controllers\HomeroomTeacher;

use App\Controllers\BaseController;
use App\Libraries\ExcelImporter;
use App\Validation\StudentValidation;

/**
 * File Path: app/Controllers/HomeroomTeacher/StudentImportController.php
 *
 * Fitur: Impor Data Siswa dan Orang Tua.
 * Peran/izin: Wali Kelas dengan permission import_export_data.
 * Relasi: memakai ExcelImporter, tabel users, students, parents, classes, dan view homeroom_teacher/students/import.
 */
class StudentImportController extends BaseController
{
    private ExcelImporter $excelImporter;

    public function __construct()
    {
        $this->excelImporter = new ExcelImporter();
    }

    public function import()
    {
        $classes = $this->homeroomClasses();
        $classNames = array_column($classes, 'class_name');

        return view('homeroom_teacher/students/import', [
            'title'      => 'Impor Data Siswa dan Orang Tua',
            'page_title' => 'Impor Data Siswa dan Orang Tua',
            'breadcrumb' => [
                ['title' => 'Wali Kelas', 'link' => base_url('homeroom/dashboard')],
                ['title' => 'Data Siswa', 'link' => base_url('homeroom/students')],
                ['title' => 'Impor Data', 'link' => null],
            ],
            'classes'     => $classes,
            'formAction'  => base_url('homeroom/students/do-import'),
            'templateUrl' => base_url('homeroom/students/download-template'),
            'backUrl'     => base_url('homeroom/my-class'),
            'scopeNote'   => empty($classNames)
                ? 'Anda belum memiliki kelas binaan aktif. Impor dapat dilakukan setelah kelas binaan ditetapkan.'
                : 'Impor Wali Kelas hanya memproses siswa pada kelas binaan: ' . implode(', ', $classNames) . '. Data orang tua diisi dalam template yang sama.',
        ]);
    }

    public function downloadTemplate()
    {
        try {
            $filename = 'template_import_siswa_' . date('Y-m-d') . '.xlsx';
            $savePath = WRITEPATH . 'uploads/' . $filename;

            $this->excelImporter->generateTemplate($savePath);

            if (!is_file($savePath)) {
                return redirect()->back()->with('error', 'Gagal membuat template. File tidak ditemukan.');
            }

            return $this->response->download($savePath, null)
                ->setFileName($filename)
                ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } catch (\Throwable $e) {
            log_message('error', 'Error generating homeroom import template: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membuat template: ' . $e->getMessage());
        }
    }

    public function doImport()
    {
        $classes = $this->homeroomClasses();
        $classNames = array_values(array_filter(array_map('strval', array_column($classes, 'class_name'))));

        if (empty($classNames)) {
            return redirect()->to(base_url('homeroom/students/import'))
                ->with('error', 'Anda belum memiliki kelas binaan aktif untuk impor data siswa.');
        }

        if (!$this->validate(StudentValidation::importRules())) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('import_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File yang diunggah tidak valid.');
        }

        $filePath = null;
        try {
            $uploadPath = WRITEPATH . 'uploads/imports/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $newFileName = 'homeroom_import_' . date('YmdHis') . '_' . uniqid('', true) . '.' . $file->getExtension();
            if (!$file->move($uploadPath, $newFileName)) {
                throw new \RuntimeException('Gagal memindahkan file unggahan.');
            }

            $filePath = $uploadPath . $newFileName;
            $results = $this->excelImporter->importStudents($filePath, [
                'auto_create_classes' => false,
                'allowed_class_names' => $classNames,
            ]);

            if (is_file($filePath)) {
                unlink($filePath);
            }

            $total  = (int) ($results['total_rows'] ?? 0);
            $ok     = (int) ($results['success'] ?? 0);
            $failed = (int) ($results['failed'] ?? 0);
            $message = sprintf('Impor selesai. Total: %d baris, berhasil: %d, gagal: %d.', $total, $ok, $failed);

            if (!empty($results['warnings'])) {
                session()->setFlashdata('import_warnings', $results['warnings']);
            }

            if ($failed > 0) {
                session()->setFlashdata('import_errors', $results['errors'] ?? []);
                session()->setFlashdata($ok > 0 ? 'warning' : 'error', $message);

                return redirect()->to(base_url('homeroom/students/import'));
            }

            return redirect()->to(base_url('homeroom/students'))
                ->with('success', $message);
        } catch (\Throwable $e) {
            if ($filePath && is_file($filePath)) {
                unlink($filePath);
            }

            log_message('error', 'Homeroom import error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat impor: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function homeroomClasses(): array
    {
        $userId = (int) (session('user_id') ?? session('id') ?? 0);
        if ($userId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('classes c')
            ->select('c.id, c.class_name, c.grade_level, c.major')
            ->where('c.homeroom_teacher_id', $userId)
            ->where('c.is_active', 1);

        if ($this->tableHasColumn('classes', 'deleted_at')) {
            $builder->where('c.deleted_at', null);
        }

        return $builder
            ->orderBy('c.grade_level', 'ASC')
            ->orderBy('c.class_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        try {
            return \Config\Database::connect()->fieldExists($column, $table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
