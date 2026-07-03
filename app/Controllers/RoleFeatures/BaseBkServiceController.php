<?php

/**
 * File: app/Controllers/RoleFeatures/BaseBkServiceController.php
 * Fitur: Controller dasar Bimbingan, Konseling, Kolaborasi Orang Tua,
 * Kunjungan Rumah, dan Konferensi Kasus.
 * Peran/izin: Dipakai controller turunan per peran. Koordinator BK/Guru BK
 * dapat mengelola; Wali Kelas/Siswa/Orang Tua dibatasi lihat detail sesuai
 * cakupan data.
 * Berhubungan dengan: BkServiceService dan view per peran pada folder
 * koordinator/counselor/homeroom_teacher/student/parent.
 */

namespace App\Controllers\RoleFeatures;

use App\Controllers\BaseController;
use App\Services\BkServiceService;

abstract class BaseBkServiceController extends BaseController
{
    protected string $roleKey = '';
    protected string $roleLabel = '';
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected string $serviceType = '';
    protected bool $canManage = false;

    protected BkServiceService $service;

    public function __construct()
    {
        helper(['form', 'url', 'auth', 'permission', 'notification']);
        $this->service = new BkServiceService();
    }

    public function index()
    {
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'status' => trim((string) $this->request->getGet('status')),
            'class_id' => trim((string) $this->request->getGet('class_id')),
            'student_id' => trim((string) $this->request->getGet('student_id')),
            'date_from' => trim((string) $this->request->getGet('date_from')),
            'date_to' => trim((string) $this->request->getGet('date_to')),
        ];

        return $this->render('index', [
            'title' => $this->service->meta($this->serviceType)['title'],
            'rows' => $this->service->list($this->serviceType, $this->roleKey, $this->currentUserId(), $filters),
            'filters' => $filters,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
        ]);
    }

    public function create()
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        return $this->render('form', [
            'title' => 'Tambah ' . $this->service->meta($this->serviceType)['title'],
            'row' => [],
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
            'action' => site_url($this->routePrefix . '/store'),
        ]);
    }

    public function store()
    {
        if (! $this->canManage) {
            return $this->deny();
        }
        $post = $this->enforcePic($this->request->getPost() ?? []);
        if ($err = $this->validateRequired($post)) {
            return redirect()->back()->withInput()->with('error', $err);
        }

        $id = $this->service->create($this->serviceType, $post, $this->currentUserId());

        $this->notifyScheduleParticipants((int) $id);

        return redirect()->to(site_url($this->routePrefix . '/show/' . $id))
            ->with('success', $this->service->meta($this->serviceType)['title'] . ' berhasil dicatat.');
    }

    public function show($id)
    {
        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId(), $this->serviceType);
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan atau tidak bisa diakses.');
        }

        return $this->render('show', [
            'title' => 'Detail ' . $this->service->meta($this->serviceType)['title'],
            'row' => $row,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
        ]);
    }

    public function edit($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $row = $this->service->find((int) $id, $this->roleKey, $this->currentUserId(), $this->serviceType);
        if (! $row) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan.');
        }

        return $this->render('form', [
            'title' => 'Edit ' . $this->service->meta($this->serviceType)['title'],
            'row' => $row,
            'options' => $this->service->formOptions($this->roleKey, $this->currentUserId()),
            'action' => site_url($this->routePrefix . '/update/' . (int) $id),
        ]);
    }

    public function update($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }
        // Pastikan record memang jenis layanan ini (cegah menyunting record jenis lain
        // lewat rute yang salah, yang bisa mengubah service_type & merusak data detail).
        if (! $this->service->find((int) $id, $this->roleKey, $this->currentUserId(), $this->serviceType)) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan.');
        }
        $post = $this->enforcePic($this->request->getPost() ?? [], (int) $id);
        if ($err = $this->validateRequired($post)) {
            return redirect()->back()->withInput()->with('error', $err);
        }

        $this->service->update((int) $id, $this->serviceType, $post, $this->currentUserId());

        $this->notifyScheduleParticipants((int) $id);

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))
            ->with('success', 'Data berhasil diperbarui.');
    }

    public function delete($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $uid = $this->currentUserId();
        $db  = \Config\Database::connect();

        // Hanya pembuat data yang boleh menghapus (Koordinator BK & Guru BK sama-sama
        // hanya dapat menghapus data buatannya sendiri). Edit isi tetap boleh lintas peran.
        $rec = $db->table('bk_service_records')
            ->select('id, created_by, service_type')
            ->where('id', (int) $id)
            ->where('deleted_at', null)
            ->get()->getRowArray();

        // Tidak ada, atau jenis layanannya bukan milik rute ini → anggap tidak ditemukan.
        if (! $rec || (string) ($rec['service_type'] ?? '') !== $this->serviceType) {
            return redirect()->to(site_url($this->routePrefix))->with('error', 'Data tidak ditemukan.');
        }

        if ((int) ($rec['created_by'] ?? 0) !== $uid) {
            return redirect()->to(site_url($this->routePrefix))
                ->with('error', 'Anda hanya dapat menghapus data yang Anda buat sendiri.');
        }

        // Soft delete + catat penghapus agar bisa dipulihkan lewat Tempat Sampah.
        if ($db->fieldExists('deleted_by', 'bk_service_records')) {
            $db->table('bk_service_records')->where('id', (int) $id)
                ->update(['deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $uid]);
        } else {
            $this->service->delete((int) $id);
        }

        return redirect()->to(site_url($this->routePrefix))
            ->with('success', 'Data dipindahkan ke Tempat Sampah.');
    }

    public function addNote($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $this->service->addNote((int) $id, $this->request->getPost() ?? [], $this->currentUserId());

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))
            ->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function updateParticipant($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $recordId = (int) ($this->request->getPost('record_id') ?? 0);
        $this->service->updateParticipant((int) $id, $this->request->getPost() ?? []);

        return redirect()->to(site_url($this->routePrefix . '/show/' . $recordId))
            ->with('success', 'Kehadiran peserta berhasil diperbarui.');
    }

    public function addParticipant($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $post = $this->request->getPost() ?? [];
        $this->service->addSingleParticipant((int) $id, $post);

        return redirect()->to(site_url($this->routePrefix . '/show/' . (int) $id))
            ->with('success', 'Peserta berhasil ditambahkan.');
    }

    public function deleteParticipant($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $recordId = (int) ($this->request->getPost('record_id') ?? 0);
        $back = (string) ($this->request->getPost('back') ?? 'show');
        $this->service->deleteParticipant((int) $id, $recordId, $this->currentUserId());

        $target = $back === 'edit'
            ? site_url($this->routePrefix . '/edit/' . $recordId)
            : site_url($this->routePrefix . '/show/' . $recordId);

        return redirect()->to($target)->with('success', 'Peserta berhasil dihapus.');
    }

    public function deleteNote($id)
    {
        if (! $this->canManage) {
            return $this->deny();
        }

        $recordId = (int) ($this->request->getPost('record_id') ?? 0);
        $ok = $this->service->deleteNote((int) $id, $recordId, $this->currentUserId());

        return redirect()->to(site_url($this->routePrefix . '/show/' . $recordId))
            ->with($ok ? 'success' : 'error', $ok ? 'Catatan berhasil dihapus.' : 'Anda hanya dapat menghapus catatan yang Anda buat sendiri.');
    }

    /**
     * Validasi field wajib (Perbaikan Kedua / Versi 2). Mengembalikan pesan
     * kesalahan berbahasa sederhana, atau null bila lolos.
     *
     * Wajib: Judul, Penanggung Jawab (kecuali Konferensi Kasus oleh Guru BK yang
     * ditetapkan Koordinator), Tanggal & Jam, Lama Kegiatan, Tempat/Lokasi/Alamat,
     * serta field deskripsi utama sesuai jenis layanan. Siswa/Kelas/peserta TIDAK wajib.
     */
    protected function validateRequired(array $post): ?string
    {
        $req = static fn(string $key): bool => trim((string) ($post[$key] ?? '')) !== '';

        if (! $req('title')) {
            return 'Judul/Topik/Masalah wajib diisi.';
        }

        // Penanggung Jawab wajib, kecuali Konferensi Kasus yang dibuat Guru BK
        // (PIC ditetapkan Koordinator BK).
        $pjOptional = ($this->serviceType === 'Konferensi Kasus' && $this->roleKey === 'guru-bk');
        if (! $pjOptional && (int) ($post['counselor_id'] ?? 0) <= 0) {
            return 'Penanggung Jawab wajib dipilih.';
        }

        if (! $req('scheduled_at') && ! $req('scheduled_date')) {
            return 'Tanggal & Jam Kegiatan wajib diisi.';
        }
        // Tanggal & Jam harus benar-benar valid (tolak jam/tanggal tidak logis
        // seperti 99:99 atau 30 Februari) agar tidak tersimpan sebagai 0000-00-00.
        $dtRaw = trim((string) ($post['scheduled_at'] ?? ''));
        if ($dtRaw === '') {
            $dtRaw = trim((string) ($post['scheduled_date'] ?? '') . ' ' . (string) ($post['scheduled_time'] ?? ''));
        }
        if (! $this->isValidDateTime($dtRaw)) {
            return 'Tanggal & Jam Kegiatan tidak valid.';
        }
        // Tidak boleh MENJADWALKAN kegiatan di masa lampau (tanggal kunjungan/kegiatan
        // sebelum hari ini). Hanya ditegakkan saat status 'Dijadwalkan' (rencana ke
        // depan). Perekaman kegiatan yang sudah berlangsung/selesai (Berlangsung,
        // Selesai, Dibatalkan, Perlu Tindak Lanjut) atau Draft tetap boleh tanggal lampau.
        $status = trim((string) ($post['status'] ?? 'Dijadwalkan')) ?: 'Dijadwalkan';
        if ($status === 'Dijadwalkan') {
            $datePart = substr(trim(str_replace('T', ' ', $dtRaw)), 0, 10);
            if ($datePart !== '' && $datePart < date('Y-m-d')) {
                return 'Tanggal kegiatan tidak boleh di masa lampau untuk kegiatan yang dijadwalkan.';
            }
        }
        if ((int) ($post['duration_minutes'] ?? 0) <= 0) {
            return 'Lama Kegiatan (menit) wajib diisi.';
        }
        if (! $req('location')) {
            return 'Tempat/Lokasi/Alamat wajib diisi.';
        }

        // Field deskripsi utama per jenis layanan.
        $descField = [
            'Bimbingan'             => 'summary',
            'Konseling'             => 'problem_description',
            'Kolaborasi Orang Tua'  => 'summary',
            'Kunjungan Rumah'       => 'visit_result',
            'Konferensi Kasus'      => 'chronology',
        ][$this->serviceType] ?? '';
        if ($descField !== '' && ! $req($descField)) {
            return 'Bagian deskripsi/ringkasan wajib diisi.';
        }

        return null;
    }

    /**
     * Tegakkan aturan Penanggung Jawab (PIC) di SISI SERVER (jangan percaya form):
     * Guru BK hanya boleh menugaskan DIRINYA sendiri. Memilih Guru BK/Petugas lain
     * adalah wewenang Koordinator BK (lihat Matriks CRUD §6).
     *
     * Khusus Konferensi Kasus: PJ ditetapkan Koordinator BK — Guru BK TIDAK boleh
     * memilih PIC sama sekali. Saat MEMBUAT, PIC dikosongkan (menunggu penetapan
     * Koordinator). Saat MENYUNTING, PIC yang sudah ada (bila Koordinator telah
     * menetapkan) DIPERTAHANKAN agar tidak terhapus oleh Guru BK.
     *
     * @param int|null $recordId id record yang sedang disunting (null saat membuat).
     */
    protected function enforcePic(array $post, ?int $recordId = null): array
    {
        if ($this->roleKey !== 'guru-bk') {
            return $post;
        }

        if ($this->serviceType === 'Konferensi Kasus') {
            $post['counselor_id'] = $recordId ? $this->service->counselorIdOf($recordId) : null;
        } else {
            $post['counselor_id'] = $this->currentUserId();
        }

        return $post;
    }

    /**
     * Validasi tanggal & jam benar-benar valid (kalender + rentang jam/menit).
     * Menerima format "Y-m-dTH:i", "Y-m-d H:i", atau "Y-m-d H:i:s".
     */
    protected function isValidDateTime(string $value): bool
    {
        $value = trim(str_replace('T', ' ', $value));
        if ($value === '') {
            return false;
        }

        $parts    = preg_split('/\s+/', $value);
        $datePart = $parts[0] ?? '';
        $timePart = $parts[1] ?? '00:00';

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datePart, $d)) {
            return false;
        }
        if (! checkdate((int) $d[2], (int) $d[3], (int) $d[1])) {
            return false;
        }
        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $timePart, $t)) {
            return false;
        }
        if ((int) $t[1] > 23 || (int) $t[2] > 59 || (isset($t[3]) && (int) $t[3] > 59)) {
            return false;
        }

        return true;
    }

    protected function render(string $view, array $data = [])
    {
        $meta = $this->service->meta($this->serviceType);
        $viewPath = $this->viewPrefix . '/' . $meta['slug'] . '/' . $view;

        return view($viewPath, array_merge([
            'serviceType' => $this->serviceType,
            'meta' => $meta,
            'roleKey' => $this->roleKey,
            'roleLabel' => $this->roleLabel,
            'routePrefix' => trim($this->routePrefix, '/'),
            'canManage' => $this->canManage,
        ], $data));
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? session('id') ?? 0);
    }

    protected function deny()
    {
        return redirect()->to(site_url($this->routePrefix))->with('error', 'Akses tidak tersedia untuk peran ini.');
    }

    /**
     * Beritahu peserta/undangan (Siswa & Orang Tua) tentang jadwal kegiatan BK.
     * Hanya RINGKASAN AMAN (tanpa topik/detail) sesuai aturan kerahasiaan:
     * Siswa/Orang Tua hanya boleh melihat jadwal, bukan detail catatan layanan BK.
     */
    protected function notifyScheduleParticipants(int $recordId): void
    {
        if ($recordId <= 0) {
            return;
        }

        $me = $this->currentUserId();
        foreach ($this->service->notifiableUserIds($recordId) as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0 || $uid === $me) {
                continue;
            }
            send_notification(
                $uid,
                'Jadwal Kegiatan/Acara BK',
                'Ada jadwal kegiatan/acara BK untuk Anda. Silakan cek halaman Jadwal Kegiatan/Acara BK.',
                'session',
                ['bk_service_record_id' => $recordId],
                // Wali Kelas/Siswa/Orang Tua → halaman terpadu Jadwal Kegiatan/Acara BK.
                bk_schedule_link($uid)
            );
        }
    }
}
