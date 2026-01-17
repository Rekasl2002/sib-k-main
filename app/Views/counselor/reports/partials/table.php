<?php
/**
 * File Path: app/Views/counselor/reports/partials/table.php
 *
 * Counselor • Report Preview Table (AJAX partial)
 * - Render tabel fleksibel: mendukung rows numerik/associative
 * - Mendukung columns sebagai:
 *   (A) list label: ['NISN','NIS','NAMA', ...]
 *   (B) mapping key=>label: ['nisn'=>'NISN','nis'=>'NIS','full_name'=>'NAMA', ...]  (RECOMMENDED)
 * - Jika columns label tidak sama dengan key row, akan dicocokkan (case-insensitive + normalized + alias)
 */

$title       = $title ?? 'Laporan';
$columns     = $columns ?? [];
$rows        = $rows ?? [];
$periodLabel = $periodLabel ?? null;

if (!function_exists('rp_norm')) {
    function rp_norm($s): string
    {
        $s = strtolower(trim((string)$s));
        $s = preg_replace('/[^a-z0-9]+/i', '', $s);
        return $s ?: '';
    }
}

// helper: cek apakah array associative mapping
$toAssoc = static function (array $arr): bool {
    $keys = array_keys($arr);
    return array_keys($keys) !== $keys;
};

// Alias umum: label tampilan -> kemungkinan key di row
$aliases = [
    'nisn'      => ['nisn'],
    'nis'       => ['nis'],
    'nama'      => ['full_name', 'name', 'student_name', 'nama'],
    'jk'        => ['gender', 'sex', 'jenis_kelamin', 'jk'],
    'tgllahir'  => ['birth_date', 'dob', 'date_of_birth', 'tanggal_lahir', 'tgl_lahir'],
    'status'    => ['status'],
    'kelas'     => ['class_name', 'kelas', 'class'],

    // umum untuk report lain
    'tanggal'   => ['date', 'created_at', 'session_date', 'violation_date', 'started_at'],

    // ✅ Tambahan untuk laporan Sesi Konseling & Kasus/Pelanggaran
    'waktu'      => ['time', 'session_time', 'violation_time', 'waktu'],
    'lokasi'     => ['location', 'lokasi', 'place', 'room', 'ruang'],
    'topik'      => ['topic', 'topik', 'subject'],
    'kategori'   => ['kategori', 'category', 'category_name', 'violation_category'],
    'siswa'      => ['student', 'student_name', 'full_name', 'nama'],
    'siswakelas' => ['student', 'student_label', 'student_or_class', 'target', 'target_label', 'target_class', 'student_class', 'participants'],
    'berulang'   => ['is_repeat_offender', 'repeat_offender', 'is_repeat', 'repeat', 'berulang'],

    'jenis'     => ['type', 'session_type', 'category_name', 'level'],
    'durasi'    => ['duration', 'duration_minutes', 'minutes'],
    'poin'      => ['points', 'point', 'point_deduction', 'total_points'],
    'nilai'     => ['percentage', 'score', 'avg_percentage'],
    'asesmen'   => ['assessment', 'assessment_title', 'title'],
    'konselor'  => ['counselor', 'counselor_name', 'counselor_full_name', 'full_name'],
];

// --- Normalisasi columns jadi (keys, labels)
$columnKeys = [];
$columnLabels = [];

// Jika columns dikirim sebagai mapping key=>label (recommended)
if (is_array($columns) && !empty($columns) && $toAssoc($columns)) {
    $columnKeys   = array_keys($columns);
    $columnLabels = array_values($columns);
} else {
    // columns sebagai list label
    $columnLabels = array_map(static fn($v) => (string)$v, (array)$columns);
    $columnKeys   = $columnLabels; // sementara, nanti dicocokkan ke row keys via alias
}

// Empty state columns fallback
if (empty($columnLabels)) {
    $columnLabels = ['Data'];
    $columnKeys   = ['Data'];
}

?>
<div class="table-responsive">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <div class="fw-semibold"><?= esc($title) ?></div>
      <?php if (!empty($periodLabel)): ?>
        <div class="text-muted small"><?= esc($periodLabel) ?></div>
      <?php endif; ?>
    </div>
    <div class="text-muted small">Total baris: <?= (int)count($rows) ?></div>
  </div>

  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <?php foreach ($columnLabels as $h): ?>
          <th class="text-uppercase small"><?= esc($h) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $row): ?>
          <?php
          // row bisa array assoc atau numerik atau scalar
          if (!is_array($row)) {
              $row = [(string)$row];
          }

          // buat map normalizedKey => originalKey untuk row assoc
          $rowNormMap = [];
          foreach ($row as $k => $_v) {
              $nk = rp_norm($k);
              if ($nk !== '') $rowNormMap[$nk] = $k;
          }

          // queue fallback: urutan nilai row (seperti di PDF/XLSX)
          $queue = array_values($row);
          $qi = 0;

          $cells = [];

          foreach ($columnKeys as $i => $k) {
              $val = '';

              // kalau columns mapping key=>label, $k adalah key row
              if ($toAssoc((array)$columns)) {
                  $val = $row[$k] ?? '';
              } else {
                  // columns list label, coba cocokkan label -> key row
                  $label = (string)$k;

                  // 1) direct key
                  if (array_key_exists($label, $row)) {
                      $val = $row[$label];
                  } else {
                      // 2) normalized key
                      $nk = rp_norm($label);
                      if ($nk !== '' && isset($rowNormMap[$nk])) {
                          $ok = $rowNormMap[$nk];
                          $val = $row[$ok] ?? '';
                      } else {
                          // 3) alias mapping
                          if ($nk !== '' && isset($aliases[$nk])) {
                              foreach ($aliases[$nk] as $cand) {
                                  if (array_key_exists($cand, $row)) {
                                      $val = $row[$cand];
                                      break;
                                  }
                                  $nc = rp_norm($cand);
                                  if ($nc !== '' && isset($rowNormMap[$nc])) {
                                      $ok = $rowNormMap[$nc];
                                      $val = $row[$ok] ?? '';
                                      break;
                                  }
                              }
                          }

                          // 4) fallback: ambil berurutan dari queue
                          if ($val === '' && $qi < count($queue)) {
                              $val = $queue[$qi];
                              $qi++;
                          }
                      }
                  }
              }

              // stringify jika array/object
              if (is_array($val) || is_object($val)) {
                  $val = json_encode($val, JSON_UNESCAPED_UNICODE);
              }

              $cells[] = $val;
          }

          // pastikan jumlah cell sama dengan kolom
          if (count($cells) < count($columnLabels)) {
              $cells = array_pad($cells, count($columnLabels), '');
          } elseif (count($cells) > count($columnLabels)) {
              $cells = array_slice($cells, 0, count($columnLabels));
          }
          ?>
          <tr>
            <?php foreach ($cells as $c): ?>
              <td><?= esc((string)$c) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="<?= (int)count($columnLabels) ?>" class="text-center text-muted">(tidak ada data)</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
