<?php

namespace App\Controllers;

class DownloadController extends BaseController
{
    public function studentTemplate()
    {
        return redirect()->to(site_url('admin/students/download-template'));
    }

    public function report($filename)
    {
        return $this->downloadFromCandidates((string) $filename, [
            WRITEPATH . 'dompdf',
            WRITEPATH . 'uploads/reports',
            WRITEPATH . 'uploads',
        ]);
    }

    public function document($filename)
    {
        return $this->downloadFromCandidates((string) $filename, [
            FCPATH . 'uploads/documents',
            WRITEPATH . 'uploads/documents',
            WRITEPATH . 'uploads',
        ]);
    }

    private function downloadFromCandidates(string $filename, array $directories)
    {
        $safeName = basename($filename);
        if ($safeName === '' || $safeName !== $filename) {
            return $this->response->setStatusCode(400)->setBody('Nama file tidak valid.');
        }

        foreach ($directories as $directory) {
            $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $safeName;
            if (is_file($path)) {
                return $this->response->download($path, null);
            }
        }

        return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
    }
}
