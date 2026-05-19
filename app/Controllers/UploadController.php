<?php

namespace App\Controllers;

class UploadController extends BaseController
{
    public function profilePhoto()
    {
        return $this->storeUploadedFile('file', 'uploads/profile_photos/' . (int) session('user_id'), ['jpg', 'jpeg', 'png', 'webp']);
    }

    public function document()
    {
        return $this->storeUploadedFile('file', 'uploads/documents', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);
    }

    public function temp()
    {
        return $this->storeUploadedFile('file', 'uploads/temp', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp']);
    }

    private function storeUploadedFile(string $field, string $target, array $allowedExtensions)
    {
        $file = $this->request->getFile($field) ?: $this->request->getFile('upload');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'File upload tidak valid.']);
        }

        $extension = strtolower($file->getClientExtension());
        if (! in_array($extension, $allowedExtensions, true)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Tipe file tidak diizinkan.']);
        }

        $relativeDir = trim(str_replace('\\', '/', $target), '/');
        $absoluteDir = FCPATH . $relativeDir;
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $name = $file->getRandomName();
        $file->move($absoluteDir, $name);

        $path = $relativeDir . '/' . $name;
        return $this->response->setJSON([
            'path' => $path,
            'url'  => base_url($path),
        ]);
    }
}
