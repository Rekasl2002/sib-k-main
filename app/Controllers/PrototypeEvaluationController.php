<?php

namespace App\Controllers;

use App\Models\PrototypeEvaluationModel;
use App\Models\PrototypeEvaluationAnswerModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Form Evaluasi & Konfirmasi Penerimaan Prototipe Fitur SIB-K.
 *
 * MODUL SEMENTARA (tahap evaluasi prototipe skripsi). Saat aplikasi final,
 * hapus: controller ini, app/Views/prototype/evaluation/, grup route
 * 'prototype/evaluation/*', tombol di app/Views/prototype/index.php, models
 * PrototypeEvaluation*, dan rollback migration 2026-06-10-000001.
 */
class PrototypeEvaluationController extends BaseController
{
    /** Lima pertanyaan evaluasi (Tabel 3.9 Format Evaluasi Prototipe). */
    private const QUESTIONS = [
        1 => 'Fitur sesuai dengan kebutuhan pengguna.',
        2 => 'Alur penggunaan mudah dipahami.',
        3 => 'Data yang dimasukkan dan ditampilkan sudah lengkap.',
        4 => 'Hak akses sesuai dengan peran pengguna.',
        5 => 'Tampilan rancangan cukup jelas untuk dipahami.',
    ];

    /** Pilihan jawaban; "diterima" dan "revisi" sama-sama dihitung diterima. */
    private const ANSWERS = [
        'diterima' => 'Setuju / Diterima',
        'revisi'   => 'Setuju / Diterima dengan Revisi',
        'belum'    => 'Tidak Setuju / Belum Diterima',
    ];

    private const REVIEW_OPTIONS = [
        'semua'    => 'Sudah, seluruh fitur yang dapat saya akses',
        'sebagian' => 'Baru sebagian fitur',
        'belum'    => 'Belum sempat melihat',
    ];

    // ---------------------------------------------------------------------
    // Halaman pengisian
    // ---------------------------------------------------------------------

    public function index()
    {
        if ($guard = $this->guardForm()) {
            return $guard;
        }

        $role = $this->currentRole();

        return view('prototype/evaluation/form', [
            'title'         => 'Evaluasi dan Konfirmasi Penerimaan Prototipe Fitur SIB-K',
            'introTitle'    => 'Evaluasi dan Konfirmasi Penerimaan Prototipe Fitur SIB-K',
            'introHtml'     => $this->introHtml(),
            'role'          => $role,
            'roleLabel'     => $this->roleLabel($role),
            'roleRespondentOptions' => $this->respondentRoleOptions(),
            'features'      => $this->accessibleFeatures($role),
            'questions'     => self::QUESTIONS,
            'answerOptions' => self::ANSWERS,
            'reviewOptions' => self::REVIEW_OPTIONS,
            'submitUrl'     => base_url('prototype/evaluation/submit'),
        ]);
    }

    public function submit()
    {
        if ($guard = $this->guardForm()) {
            return $guard;
        }

        $role     = $this->currentRole();
        $features = $this->accessibleFeatures($role);
        $post     = $this->request->getPost();

        // Validasi dasar.
        $name     = trim((string) ($post['respondent_name'] ?? ''));
        $relation = trim((string) ($post['respondent_relation'] ?? ''));
        $reviewed = (string) ($post['reviewed_prototype'] ?? '');
        $errors   = [];

        if (empty($post['consent_participate']) || empty($post['consent_data_usage'])) {
            $errors[] = 'Konfirmasi kesediaan dan persetujuan penggunaan data wajib dicentang.';
        }
        if ($name === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        }
        if ($relation === '') {
            $errors[] = 'Kelas / hubungan dengan siswa / peran di sekolah wajib diisi.';
        }
        if (! isset(self::REVIEW_OPTIONS[$reviewed])) {
            $errors[] = 'Pilih status apakah sudah melihat prototipe.';
        }

        $postedAnswers = (array) ($post['answers'] ?? []);
        $answerRows    = [];
        foreach ($features as $key => $feature) {
            foreach (self::QUESTIONS as $no => $text) {
                $value = (string) ($postedAnswers[$key][$no] ?? '');
                if (! isset(self::ANSWERS[$value])) {
                    $errors[] = 'Masih ada pertanyaan yang belum dijawab pada fitur "' . $feature['title'] . '".';
                    break 2;
                }
                $answerRows[] = [
                    'feature_key'   => $key,
                    'feature_title' => $feature['title'],
                    'category'      => $feature['category'],
                    'question_no'   => $no,
                    'question_text' => $text,
                    'answer'        => $value,
                ];
            }
        }

        if ($errors !== []) {
            return redirect()->to(base_url('prototype/evaluation'))
                ->with('eval_errors', $errors)
                ->withInput();
        }

        // Catatan opsional per fitur (hanya yang dapat diakses & terisi).
        $postedNotes  = (array) ($post['feature_notes'] ?? []);
        $featureNotes = [];
        foreach ($features as $key => $feature) {
            $note = trim((string) ($postedNotes[$key] ?? ''));
            if ($note !== '') {
                $featureNotes[$key] = $note;
            }
        }

        $now            = date('Y-m-d H:i:s');
        $evaluationModel = new PrototypeEvaluationModel();
        $evaluationId    = $evaluationModel->insert([
            'user_id'                  => (int) (session('user_id') ?? 0) ?: null,
            'respondent_name'          => $name,
            'respondent_relation'      => $relation,
            'respondent_role'          => $role,
            'role_label'               => $this->roleLabel($role),
            'consent_participate'      => 1,
            'consent_data_usage'       => 1,
            'reviewed_prototype'       => $reviewed,
            'accessible_feature_count' => count($features),
            'feature_notes'            => $featureNotes !== [] ? json_encode($featureNotes, JSON_UNESCAPED_UNICODE) : null,
            'suggestions'              => trim((string) ($post['suggestions'] ?? '')) ?: null,
            'ip_address'               => $this->request->getIPAddress(),
            'user_agent'               => substr((string) $this->request->getUserAgent(), 0, 255),
            'submitted_at'             => $now,
        ], true);

        if ($evaluationId) {
            $answerModel = new PrototypeEvaluationAnswerModel();
            foreach ($answerRows as &$row) {
                $row['evaluation_id'] = $evaluationId;
                $row['created_at']    = $now;
            }
            unset($row);
            $answerModel->insertBatch($answerRows);
        }

        return redirect()->to(base_url('prototype/evaluation/thanks'));
    }

    public function thanks()
    {
        if ($guard = $this->guardForm()) {
            return $guard;
        }

        return view('prototype/evaluation/thanks', [
            'title'     => 'Terima Kasih - Evaluasi Prototipe SIB-K',
            'roleMode'  => $this->currentRole(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Hasil & ekspor (khusus admin/peneliti)
    // ---------------------------------------------------------------------

    public function results()
    {
        if ($guard = $this->guardAdmin()) {
            return $guard;
        }

        $evaluations = (new PrototypeEvaluationModel())->orderBy('submitted_at', 'DESC')->findAll();
        $answers     = (new PrototypeEvaluationAnswerModel())->findAll();

        return view('prototype/evaluation/results', [
            'title'       => 'Hasil Evaluasi Prototipe SIB-K',
            'evaluations' => $this->decorateEvaluations($evaluations, $answers),
            'summary'     => $this->buildSummary($answers),
            'features'    => $this->featureCatalog(),
            'questions'   => self::QUESTIONS,
            'totalRespondents' => count($evaluations),
            'exportUrl'   => base_url('prototype/evaluation/export'),
        ]);
    }

    public function resultDetail($id = null)
    {
        if ($guard = $this->guardAdmin()) {
            return $guard;
        }

        $id         = (int) $id;
        $evaluation = (new PrototypeEvaluationModel())->find($id);
        if (! $evaluation) {
            return redirect()->to(base_url('prototype/evaluation/results'))
                ->with('error', 'Data evaluasi tidak ditemukan.');
        }

        $answers = (new PrototypeEvaluationAnswerModel())
            ->where('evaluation_id', $id)
            ->orderBy('id', 'ASC')
            ->findAll();

        // Kelompokkan jawaban per fitur.
        $grouped = [];
        foreach ($answers as $row) {
            $grouped[$row['feature_key']]['title'] = $row['feature_title'];
            $grouped[$row['feature_key']]['category'] = $row['category'];
            $grouped[$row['feature_key']]['answers'][(int) $row['question_no']] = $row['answer'];
        }

        return view('prototype/evaluation/result_detail', [
            'title'         => 'Detail Evaluasi - ' . $evaluation['respondent_name'],
            'evaluation'    => $evaluation,
            'grouped'       => $grouped,
            'featureNotes'  => $this->decodeNotes($evaluation['feature_notes'] ?? null),
            'questions'     => self::QUESTIONS,
            'answerOptions' => self::ANSWERS,
            'reviewOptions' => self::REVIEW_OPTIONS,
            'pct'           => $this->acceptancePct($answers),
        ]);
    }

    public function export()
    {
        if ($guard = $this->guardAdmin()) {
            return $guard;
        }

        $evaluations = (new PrototypeEvaluationModel())->orderBy('submitted_at', 'ASC')->findAll();
        $answers     = (new PrototypeEvaluationAnswerModel())->findAll();

        $answersByEval = [];
        foreach ($answers as $row) {
            $answersByEval[(int) $row['evaluation_id']][] = $row;
        }

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Responden
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Responden');
        $head = ['No', 'Tanggal', 'Nama', 'Peran', 'Kelas/Hubungan/Peran', 'Bersedia', 'Setuju Data', 'Sudah Lihat Prototipe', 'Jumlah Fitur Dinilai', '% Penerimaan', 'Kategori', 'Saran/Revisi'];
        $ws->fromArray($head, null, 'A1');
        $r = 2;
        $no = 1;
        foreach ($evaluations as $e) {
            $pct = $this->acceptancePct($answersByEval[(int) $e['id']] ?? []);
            $ws->fromArray([
                $no++,
                $e['submitted_at'],
                $e['respondent_name'],
                $e['role_label'],
                $e['respondent_relation'],
                $e['consent_participate'] ? 'Ya' : 'Tidak',
                $e['consent_data_usage'] ? 'Ya' : 'Tidak',
                self::REVIEW_OPTIONS[$e['reviewed_prototype']] ?? $e['reviewed_prototype'],
                $e['accessible_feature_count'],
                $pct['percent'] . '%',
                $pct['category'],
                $e['suggestions'],
            ], null, 'A' . $r++);
        }

        // Sheet 2: Jawaban (format panjang)
        $ws2 = $spreadsheet->createSheet();
        $ws2->setTitle('Jawaban');
        $ws2->fromArray(['No', 'Responden', 'Peran', 'Fitur', 'No', 'Pertanyaan', 'Jawaban'], null, 'A1');
        $r = 2;
        $no = 1;
        foreach ($evaluations as $e) {
            foreach (($answersByEval[(int) $e['id']] ?? []) as $a) {
                $ws2->fromArray([
                    $no++,
                    $e['respondent_name'],
                    $e['role_label'],
                    $a['feature_title'],
                    $a['question_no'],
                    $a['question_text'],
                    self::ANSWERS[$a['answer']] ?? $a['answer'],
                ], null, 'A' . $r++);
            }
        }

        // Sheet 3: Catatan per fitur
        $ws3 = $spreadsheet->createSheet();
        $ws3->setTitle('Catatan Fitur');
        $ws3->fromArray(['No', 'Responden', 'Peran', 'Fitur', 'Catatan/Revisi'], null, 'A1');
        $r = 2;
        $no = 1;
        $catalog = $this->featureCatalog();
        foreach ($evaluations as $e) {
            foreach ($this->decodeNotes($e['feature_notes'] ?? null) as $key => $note) {
                $ws3->fromArray([
                    $no++,
                    $e['respondent_name'],
                    $e['role_label'],
                    $catalog[$key]['title'] ?? $key,
                    $note,
                ], null, 'A' . $r++);
            }
        }

        // Sheet 4: Rekap per fitur x pertanyaan
        $ws4 = $spreadsheet->createSheet();
        $ws4->setTitle('Rekap');
        $ws4->fromArray(['Fitur', 'No', 'Pertanyaan', 'Diterima', 'Diterima dgn Revisi', 'Belum Diterima', 'Jumlah Responden', '% Penerimaan'], null, 'A1');
        $summary = $this->buildSummary($answers);
        $r = 2;
        foreach ($summary as $key => $feat) {
            foreach (self::QUESTIONS as $qno => $qtext) {
                $cell = $feat['questions'][$qno];
                $ws4->fromArray([
                    $feat['title'],
                    $qno,
                    $qtext,
                    $cell['diterima'],
                    $cell['revisi'],
                    $cell['belum'],
                    $cell['total'],
                    $cell['percent'] . '%',
                ], null, 'A' . $r++);
            }
        }

        foreach ([$ws, $ws2, $ws3, $ws4] as $sheet) {
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Hasil_Evaluasi_Prototipe_SIBK_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    private function guardForm()
    {
        helper('simulation_access');
        if (can_access_simulation_suite()) {
            return null;
        }

        return redirect()->to('/dashboard')
            ->with('error', 'Akses formulir evaluasi prototipe belum diberikan oleh admin.');
    }

    private function guardAdmin()
    {
        helper('simulation_access');
        if (simulation_access_is_admin()) {
            return null;
        }

        return redirect()->to(base_url('prototype/evaluation'))
            ->with('error', 'Hanya admin/peneliti yang dapat melihat hasil evaluasi.');
    }

    private function currentRole(): string
    {
        $role = strtolower(trim(str_replace([' ', '_'], '-', (string) (session('role_name') ?? ''))));
        $map  = [
            'administrator' => 'admin', 'admin' => 'admin',
            'koordinator' => 'koordinator-bk', 'koordinator-bk' => 'koordinator-bk',
            'guru-bk' => 'guru-bk', 'counselor' => 'guru-bk', 'konselor' => 'guru-bk',
            'wali-kelas' => 'wali-kelas', 'homeroom' => 'wali-kelas',
            'siswa' => 'siswa', 'student' => 'siswa',
            'orang-tua' => 'orang-tua', 'parent' => 'orang-tua', 'ortu' => 'orang-tua',
        ];
        if (isset($map[$role])) {
            return $map[$role];
        }

        return match ((int) (session('role_id') ?? 0)) {
            1 => 'admin',
            2 => 'koordinator-bk',
            3 => 'guru-bk',
            4 => 'wali-kelas',
            5 => 'siswa',
            6 => 'orang-tua',
            default => 'guru-bk',
        };
    }

    private function roleLabel(string $role): string
    {
        return [
            'admin'          => 'Admin',
            'koordinator-bk' => 'Koordinator BK',
            'guru-bk'        => 'Guru BK',
            'wali-kelas'     => 'Wali Kelas',
            'siswa'          => 'Siswa',
            'orang-tua'      => 'Orang Tua',
        ][$role] ?? 'Pengguna';
    }

    private function respondentRoleOptions(): array
    {
        return [
            'koordinator-bk' => 'Koordinator BK',
            'guru-bk'        => 'Guru BK',
            'wali-kelas'     => 'Wali Kelas',
            'siswa'          => 'Siswa',
            'orang-tua'      => 'Orang Tua',
        ];
    }

    private function roleAllowed(string $role, array $modes): bool
    {
        return in_array('all', $modes, true) || in_array($role, $modes, true) || $role === 'admin';
    }

    /**
     * Daftar 14 fitur yang dievaluasi beserta hak akses peran.
     * role_modes disalin dari PrototypeController::features() agar fitur yang
     * tidak dapat diakses sebuah peran (mis. Penugasan untuk Siswa) tidak dinilai.
     */
    private function featureCatalog(): array
    {
        $all = ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas', 'siswa', 'orang-tua'];

        return [
            // Fitur yang direncanakan untuk ditambahkan
            'consultation'         => ['title' => 'Konsultasi dan Pengaduan', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'notifications'        => ['title' => 'Notifikasi Internal', 'category' => 'Ditambahkan', 'role_modes' => ['all']],
            'messages'             => ['title' => 'Pesan Internal', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'assessments'          => ['title' => 'Asesmen', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'career-study'         => ['title' => 'Info Karier dan Studi Lanjut', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'guidance'             => ['title' => 'Bimbingan', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'counseling'           => ['title' => 'Konseling', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'parent-collaboration' => ['title' => 'Kolaborasi Orang Tua', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'home-visits'          => ['title' => 'Kunjungan Rumah', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'case-conferences'     => ['title' => 'Konferensi Kasus', 'category' => 'Ditambahkan', 'role_modes' => $all],
            'assignments'          => ['title' => 'Penugasan', 'category' => 'Ditambahkan', 'role_modes' => ['admin', 'koordinator-bk', 'guru-bk']],
            // Fitur yang direncanakan untuk diperbarui atau diperbaiki
            'dashboard'            => ['title' => 'Dashboard', 'category' => 'Diperbarui', 'role_modes' => ['all']],
            'reports'              => ['title' => 'Laporan', 'category' => 'Diperbarui', 'role_modes' => ['admin', 'koordinator-bk', 'guru-bk', 'wali-kelas']],
            'student-import'       => ['title' => 'Impor Data Siswa dan Orang Tua', 'category' => 'Diperbarui', 'role_modes' => ['admin', 'koordinator-bk', 'wali-kelas']],
        ];
    }

    private function accessibleFeatures(string $role): array
    {
        return array_filter(
            $this->featureCatalog(),
            fn(array $feature): bool => $this->roleAllowed($role, $feature['role_modes'])
        );
    }

    private function decodeNotes($raw): array
    {
        if (! is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** Hitung persentase penerimaan: "diterima" + "revisi" sama-sama diterima. */
    private function acceptancePct(array $answers): array
    {
        $total = count($answers);
        if ($total === 0) {
            return ['percent' => 0, 'category' => '-', 'accepted' => 0, 'total' => 0];
        }
        $accepted = 0;
        foreach ($answers as $a) {
            if (in_array($a['answer'], ['diterima', 'revisi'], true)) {
                $accepted++;
            }
        }
        $percent = (int) round($accepted / $total * 100);

        return [
            'percent'  => $percent,
            'category' => $this->category($percent),
            'accepted' => $accepted,
            'total'    => $total,
        ];
    }

    /** Interpretasi penerimaan (Tabel 3.13). */
    private function category(int $percent): string
    {
        if ($percent < 50) {
            return 'Belum diterima';
        }
        if ($percent <= 85) {
            return 'Diterima dengan revisi';
        }

        return 'Diterima';
    }

    private function decorateEvaluations(array $evaluations, array $answers): array
    {
        $byEval = [];
        foreach ($answers as $a) {
            $byEval[(int) $a['evaluation_id']][] = $a;
        }
        foreach ($evaluations as &$e) {
            $e['acceptance'] = $this->acceptancePct($byEval[(int) $e['id']] ?? []);
        }

        return $evaluations;
    }

    /** Rekap per fitur x pertanyaan untuk halaman hasil & ekspor. */
    private function buildSummary(array $answers): array
    {
        $catalog = $this->featureCatalog();
        $summary = [];
        foreach ($catalog as $key => $feature) {
            $summary[$key] = ['title' => $feature['title'], 'category' => $feature['category'], 'questions' => []];
            foreach (self::QUESTIONS as $no => $text) {
                $summary[$key]['questions'][$no] = ['diterima' => 0, 'revisi' => 0, 'belum' => 0, 'total' => 0, 'percent' => 0];
            }
        }

        foreach ($answers as $a) {
            $key = $a['feature_key'];
            $no  = (int) $a['question_no'];
            if (! isset($summary[$key]['questions'][$no])) {
                continue;
            }
            $ans = $a['answer'];
            if (! isset($summary[$key]['questions'][$no][$ans])) {
                continue;
            }
            $summary[$key]['questions'][$no][$ans]++;
            $summary[$key]['questions'][$no]['total']++;
        }

        foreach ($summary as &$feat) {
            foreach ($feat['questions'] as &$cell) {
                $accepted = $cell['diterima'] + $cell['revisi'];
                $cell['percent'] = $cell['total'] > 0 ? (int) round($accepted / $cell['total'] * 100) : 0;
            }
            unset($cell);
        }
        unset($feat);

        return $summary;
    }

    private function introHtml(): string
    {
        return view('prototype/evaluation/_intro');
    }
}
