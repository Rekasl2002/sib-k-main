<?php

/**
 * File: app/Services/DashboardService.php
 *
 * Layer data terpusat untuk seluruh DASHBOARD peran (Admin, Koordinator BK,
 * Guru BK, Wali Kelas, Siswa, Orang Tua). Menyediakan agregat ringkas yang
 * konsisten lintas peran: jumlah catatan kegiatan, jumlah data per fitur BK
 * (bar chart), tren bulanan (line chart), Guru BK dengan catatan terbanyak,
 * aktivitas terbaru lintas-fitur, pesan masuk belum dibaca, konsultasi belum
 * diproses, dan tugas baru.
 *
 * Cakupan ($role): 'koordinator-bk'/'admin' (seluruh sekolah), 'guru-bk'
 * (dibuat/ditangani dirinya), 'wali-kelas' (kelas binaan). Semua query
 * baca-saja, dibungkus try/catch agar dashboard tidak fatal bila data kosong.
 *
 * Berhubungan dengan: bk_service_records, assessments, consultation_complaints,
 * bk_assignments(+bk_assignment_targets), career_options, university_info,
 * message_participants, classes.
 */

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class DashboardService
{
    private BaseConnection $db;

    /** Status konsultasi yang dianggap "belum diproses/aktif". */
    private const COMPLAINT_OPEN = ['Diajukan', 'Ditinjau', 'Diterima', 'Dijadwalkan'];

    /** Status penugasan yang dianggap "berjalan". */
    private const ASSIGNMENT_OPEN = ['Ditugaskan', 'Dibaca', 'Berjalan'];

    /** 5 jenis layanan BK pada bk_service_records. */
    private const SERVICE_TYPES = ['Bimbingan', 'Konseling', 'Kolaborasi Orang Tua', 'Kunjungan Rumah', 'Konferensi Kasus'];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /* ============================================================
     * Helper cakupan
     * ============================================================ */

    private function isFullScope(string $role): bool
    {
        return in_array($role, ['admin', 'koordinator-bk', 'koordinator'], true);
    }

    /** Daftar id kelas binaan Wali Kelas. */
    private function homeroomClassIds(int $userId): array
    {
        try {
            $rows = $this->db->table('classes')
                ->select('id')
                ->where('homeroom_teacher_id', $userId)
                ->where('deleted_at', null)
                ->get()->getResultArray();
            return array_map(static fn($r) => (int) $r['id'], $rows) ?: [0];
        } catch (\Throwable $e) {
            return [0];
        }
    }

    /** Terapkan cakupan peran pada builder bk_service_records (alias bsr). */
    private function scopeRecords($builder, string $role, int $userId): void
    {
        if ($this->isFullScope($role)) {
            return;
        }
        if ($role === 'guru-bk') {
            $builder->groupStart()
                ->where('bsr.counselor_id', $userId)
                ->orWhere('bsr.created_by', $userId)
                ->groupEnd();
            return;
        }
        if ($role === 'wali-kelas') {
            $builder->whereIn('bsr.target_class_id', $this->homeroomClassIds($userId));
        }
    }

    /* ============================================================
     * Kartu kecil (statistik)
     * ============================================================ */

    /**
     * Total "Catatan Kegiatan": 5 layanan BK (bk_service_records) + Asesmen.
     */
    public function catatanKegiatanCount(string $role, int $userId): int
    {
        $total = 0;
        try {
            $b = $this->db->table('bk_service_records bsr')->where('bsr.deleted_at', null);
            $this->scopeRecords($b, $role, $userId);
            $total += (int) $b->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'catatanKegiatanCount(bsr): ' . $e->getMessage());
        }
        try {
            $a = $this->db->table('assessments')->where('deleted_at', null);
            if (! $this->isFullScope($role) && $role === 'guru-bk') {
                $a->where('created_by', $userId);
            } elseif ($role === 'wali-kelas') {
                // Wali Kelas tidak membuat asesmen → 0 tambahan.
                return $total;
            }
            $total += (int) $a->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'catatanKegiatanCount(assessment): ' . $e->getMessage());
        }
        return $total;
    }

    /** Pesan masuk belum dibaca untuk seorang pengguna. */
    public function unreadMessages(int $userId): int
    {
        try {
            return (int) $this->db->table('message_participants')
                ->where('user_id', $userId)
                ->where('role', 'recipient')
                ->where('is_read', 0)
                ->where('deleted_at', null)
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Konsultasi & Pengaduan yang belum diproses (sesuai cakupan peran). */
    public function openComplaints(string $role, int $userId): int
    {
        try {
            $b = $this->db->table('consultation_complaints')
                ->whereIn('status', self::COMPLAINT_OPEN)
                ->where('deleted_at', null);
            if ($role === 'guru-bk') {
                $b->groupStart()
                    ->where('assigned_to_user_id', $userId)
                    ->orWhere('handled_by', $userId)
                    ->groupEnd();
            }
            return (int) $b->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Penugasan berjalan yang ditujukan ke Guru BK (untuk Koordinator). */
    public function openAssignmentsAll(): int
    {
        try {
            return (int) $this->db->table('bk_assignments')
                ->whereIn('status', self::ASSIGNMENT_OPEN)
                ->where('deleted_at', null)
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Tugas BARU (status Ditugaskan) untuk seorang Guru BK (kolom utama atau pivot). */
    public function newAssignmentsForCounselor(int $userId): int
    {
        try {
            $sub = "SELECT assignment_id FROM bk_assignment_targets "
                . "WHERE target_type = 'counselor' AND user_id = " . (int) $userId
                . " AND deleted_at IS NULL";
            return (int) $this->db->table('bk_assignments ba')
                ->where('ba.deleted_at', null)
                ->whereIn('ba.status', ['Ditugaskan', 'Dibaca'])
                ->groupStart()
                    ->where('ba.assigned_to_user_id', $userId)
                    ->orWhere('ba.id IN (' . $sub . ')', null, false)
                ->groupEnd()
                ->countAllResults();
        } catch (\Throwable $e) {
            // Bila tabel pivot belum ada, fallback ke kolom utama saja.
            try {
                return (int) $this->db->table('bk_assignments')
                    ->where('deleted_at', null)
                    ->whereIn('status', ['Ditugaskan', 'Dibaca'])
                    ->where('assigned_to_user_id', $userId)
                    ->countAllResults();
            } catch (\Throwable $e2) {
                return 0;
            }
        }
    }

    /* ============================================================
     * Bar chart: jumlah data per fitur BK
     * ============================================================ */

    /**
     * Jumlah data tiap fitur: 5 layanan BK + Konsultasi & Pengaduan + Asesmen
     * + Info Karier + Info Studi Lanjut. Mengembalikan assoc [label => count].
     */
    public function featureCounts(string $role, int $userId): array
    {
        $out = [];

        // 5 layanan BK dari bk_service_records.
        $byType = array_fill_keys(self::SERVICE_TYPES, 0);
        try {
            $b = $this->db->table('bk_service_records bsr')
                ->select('bsr.service_type, COUNT(*) AS total')
                ->where('bsr.deleted_at', null)
                ->groupBy('bsr.service_type');
            $this->scopeRecords($b, $role, $userId);
            foreach ($b->get()->getResultArray() as $r) {
                $byType[$r['service_type']] = (int) $r['total'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'featureCounts(bsr): ' . $e->getMessage());
        }
        foreach (self::SERVICE_TYPES as $t) {
            $out[$t] = $byType[$t] ?? 0;
        }

        // Konsultasi & Pengaduan.
        try {
            $c = $this->db->table('consultation_complaints')->where('deleted_at', null);
            if ($role === 'guru-bk') {
                $c->groupStart()->where('assigned_to_user_id', $userId)->orWhere('handled_by', $userId)->groupEnd();
            } elseif ($role === 'wali-kelas') {
                $c->where('visible_to_homeroom', 1);
            }
            $out['Konsultasi & Pengaduan'] = (int) $c->countAllResults();
        } catch (\Throwable $e) {
            $out['Konsultasi & Pengaduan'] = 0;
        }

        // Asesmen.
        try {
            $a = $this->db->table('assessments')->where('deleted_at', null);
            if ($role === 'guru-bk') {
                $a->where('created_by', $userId);
            }
            $out['Asesmen'] = ($role === 'wali-kelas') ? (int) $this->db->table('assessments')->where('deleted_at', null)->countAllResults() : (int) $a->countAllResults();
        } catch (\Throwable $e) {
            $out['Asesmen'] = 0;
        }

        // Info Karier & Info Studi Lanjut (data sekolah, tampilkan total).
        try {
            $out['Info Karier'] = (int) $this->db->table('career_options')->where('deleted_at', null)->countAllResults();
        } catch (\Throwable $e) {
            $out['Info Karier'] = 0;
        }
        try {
            $out['Info Studi Lanjut'] = (int) $this->db->table('university_info')->where('deleted_at', null)->countAllResults();
        } catch (\Throwable $e) {
            $out['Info Studi Lanjut'] = 0;
        }

        return $out;
    }

    /* ============================================================
     * Line chart: tren catatan BK per bulan
     * ============================================================ */

    /**
     * Tren jumlah catatan layanan BK (bk_service_records) yang dibuat per bulan.
     * @return array{labels:list<string>, data:list<int>}
     */
    public function monthlyTrend(string $role, int $userId, int $months = 6): array
    {
        $labels = [];
        $index  = [];
        $data   = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $index[$ym] = count($labels);
            $labels[] = date('M Y', strtotime("-{$i} months"));
            $data[]   = 0;
        }

        try {
            $start = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
            $b = $this->db->table('bk_service_records bsr')
                ->select("DATE_FORMAT(bsr.created_at, '%Y-%m') AS ym, COUNT(*) AS total")
                ->where('bsr.deleted_at', null)
                ->where('bsr.created_at >=', $start . ' 00:00:00')
                ->groupBy('ym');
            $this->scopeRecords($b, $role, $userId);
            foreach ($b->get()->getResultArray() as $r) {
                $ym = $r['ym'] ?? null;
                if ($ym !== null && isset($index[$ym])) {
                    $data[$index[$ym]] = (int) $r['total'];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'monthlyTrend: ' . $e->getMessage());
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /* ============================================================
     * Tabel bawah
     * ============================================================ */

    /**
     * Guru BK dengan catatan terbanyak (5 layanan BK + Asesmen digabung).
     * @return list<array{name:string, total:int}>
     */
    public function topCounselorsByRecords(int $limit = 5): array
    {
        $map = [];
        try {
            $rows = $this->db->table('bk_service_records')
                ->select('counselor_id AS uid, COUNT(*) AS total')
                ->where('deleted_at', null)
                ->where('counselor_id IS NOT NULL', null, false)
                ->groupBy('counselor_id')->get()->getResultArray();
            foreach ($rows as $r) {
                $map[(int) $r['uid']] = ($map[(int) $r['uid']] ?? 0) + (int) $r['total'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'topCounselors(bsr): ' . $e->getMessage());
        }
        try {
            $rows = $this->db->table('assessments')
                ->select('created_by AS uid, COUNT(*) AS total')
                ->where('deleted_at', null)
                ->where('created_by IS NOT NULL', null, false)
                ->groupBy('created_by')->get()->getResultArray();
            foreach ($rows as $r) {
                $map[(int) $r['uid']] = ($map[(int) $r['uid']] ?? 0) + (int) $r['total'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'topCounselors(assessment): ' . $e->getMessage());
        }

        if (! $map) {
            return [];
        }

        // Sisakan hanya Guru BK (role_id = 3) dan lampirkan nama.
        $names = [];
        try {
            $users = $this->db->table('users')
                ->select('id, full_name, role_id')
                ->whereIn('id', array_keys($map))
                ->where('deleted_at', null)
                ->get()->getResultArray();
            foreach ($users as $u) {
                if ((int) $u['role_id'] === 3) {
                    $names[(int) $u['id']] = $u['full_name'];
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        $result = [];
        foreach ($map as $uid => $total) {
            if (isset($names[$uid])) {
                $result[] = ['name' => $names[$uid], 'total' => $total];
            }
        }
        usort($result, static fn($a, $b) => $b['total'] <=> $a['total']);

        return array_slice($result, 0, $limit);
    }

    /**
     * Aktivitas terbaru lintas-fitur: catatan 5 layanan BK + Asesmen +
     * Konsultasi & Pengaduan + Info Karier + Info Studi Lanjut yang BARU dibuat.
     * TIDAK termasuk pesan & notifikasi.
     * @return list<array{title:string, type:string, time:string, icon:string, color:string}>
     */
    public function recentActivities(string $role, int $userId, int $limit = 8): array
    {
        $items = [];

        // 5 layanan BK.
        try {
            $b = $this->db->table('bk_service_records bsr')
                ->select('bsr.service_type, bsr.title, bsr.created_at')
                ->where('bsr.deleted_at', null)
                ->orderBy('bsr.created_at', 'DESC')
                ->limit($limit);
            $this->scopeRecords($b, $role, $userId);
            foreach ($b->get()->getResultArray() as $r) {
                $items[] = [
                    'title' => $r['title'] ?: $r['service_type'],
                    'type'  => $r['service_type'],
                    'time'  => $r['created_at'] ?? '',
                    'icon'  => 'mdi-clipboard-text-outline',
                    'color' => 'primary',
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'recentActivities(bsr): ' . $e->getMessage());
        }

        // Asesmen.
        try {
            $a = $this->db->table('assessments')
                ->select('title, created_at')
                ->where('deleted_at', null)
                ->orderBy('created_at', 'DESC')
                ->limit($limit);
            if ($role === 'guru-bk') {
                $a->where('created_by', $userId);
            }
            foreach ($a->get()->getResultArray() as $r) {
                $items[] = [
                    'title' => $r['title'] ?? 'Asesmen',
                    'type'  => 'Asesmen',
                    'time'  => $r['created_at'] ?? '',
                    'icon'  => 'mdi-clipboard-check-outline',
                    'color' => 'success',
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'recentActivities(assessment): ' . $e->getMessage());
        }

        // Konsultasi & Pengaduan.
        try {
            $c = $this->db->table('consultation_complaints')
                ->select('title, created_at')
                ->where('deleted_at', null)
                ->orderBy('created_at', 'DESC')
                ->limit($limit);
            if ($role === 'guru-bk') {
                $c->groupStart()->where('assigned_to_user_id', $userId)->orWhere('handled_by', $userId)->groupEnd();
            }
            foreach ($c->get()->getResultArray() as $r) {
                $items[] = [
                    'title' => $r['title'] ?? 'Konsultasi & Pengaduan',
                    'type'  => 'Konsultasi & Pengaduan',
                    'time'  => $r['created_at'] ?? '',
                    'icon'  => 'mdi-message-alert-outline',
                    'color' => 'danger',
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'recentActivities(complaint): ' . $e->getMessage());
        }

        // Info Karier & Info Studi Lanjut (hanya untuk cakupan penuh agar relevan).
        if ($this->isFullScope($role) || $role === 'guru-bk') {
            try {
                foreach ($this->db->table('career_options')->select('title, created_at')
                    ->where('deleted_at', null)->orderBy('created_at', 'DESC')->limit($limit)
                    ->get()->getResultArray() as $r) {
                    $items[] = [
                        'title' => $r['title'] ?? 'Info Karier',
                        'type'  => 'Info Karier',
                        'time'  => $r['created_at'] ?? '',
                        'icon'  => 'mdi-briefcase-outline',
                        'color' => 'info',
                    ];
                }
            } catch (\Throwable $e) {
            }
            try {
                foreach ($this->db->table('university_info')->select('university_name AS title, created_at')
                    ->where('deleted_at', null)->orderBy('created_at', 'DESC')->limit($limit)
                    ->get()->getResultArray() as $r) {
                    $items[] = [
                        'title' => $r['title'] ?? 'Info Studi Lanjut',
                        'type'  => 'Info Studi Lanjut',
                        'time'  => $r['created_at'] ?? '',
                        'icon'  => 'mdi-school-outline',
                        'color' => 'secondary',
                    ];
                }
            } catch (\Throwable $e) {
            }
        }

        // Urutkan gabungan berdasarkan waktu terbaru.
        usort($items, static fn($a, $b) => strcmp((string) $b['time'], (string) $a['time']));

        return array_slice($items, 0, $limit);
    }
}
