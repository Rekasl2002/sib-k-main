<!-- app/Views/homeroom_teacher/students/show.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Load helper global untuk nomor HP / WhatsApp
helper('phone');
// ✅ Pakai helper auth agar konsisten dengan halaman lain (user_avatar)
helper('auth');

// Helpers kecil biar view tahan banting untuk array/objek
if (!function_exists('rowa')) {
    function rowa($r): array
    {
        return is_array($r)
            ? $r
            : (is_object($r) ? (array) $r : []);
    }
}

if (!function_exists('v')) {
    function v($r, string $key, $default = '')
    {
        $a = rowa($r);
        return esc($a[$key] ?? $default);
    }
}

if (!function_exists('fmt_date_id')) {
    function fmt_date_id(?string $date): string
    {
        if (empty($date)) {
            return '-';
        }

        $ts = strtotime($date);
        if (! $ts) {
            return esc($date);
        }

        // Format sederhana: 17 Ags 2025
        $bulan = [
            1  => 'Jan', 2  => 'Feb', 3  => 'Mar',
            4  => 'Apr', 5  => 'Mei', 6  => 'Jun',
            7  => 'Jul', 8  => 'Ags', 9  => 'Sep',
            10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $m = (int) date('n', $ts);

        return date('d ', $ts) . ($bulan[$m] ?? date('M', $ts)) . date(' Y', $ts);
    }
}

if (!function_exists('badge_points_class')) {
    function badge_points_class(int $points): string
    {
        if ($points >= 75) {
            return 'bg-danger';
        }
        if ($points >= 40) {
            return 'bg-warning';
        }
        if ($points > 0) {
            return 'bg-info';
        }
        return 'bg-success';
    }
}

if (!function_exists('badge_severity_class')) {
    function badge_severity_class(?string $level): string
    {
        $s = strtolower((string) $level);

        if (str_contains($s, 'berat')) {
            return 'bg-danger';
        }
        if (str_contains($s, 'sedang')) {
            return 'bg-warning';
        }
        if (str_contains($s, 'ringan')) {
            return 'bg-success';
        }

        return 'bg-secondary';
    }
}

if (!function_exists('badge_status_class')) {
    function badge_status_class(?string $status): string
    {
        $s = strtolower((string) $status);

        return match (true) {
            str_contains($s, 'selesai')     => 'bg-success',
            str_contains($s, 'proses'),
            str_contains($s, 'ditindak')    => 'bg-primary',
            str_contains($s, 'batal')       => 'bg-danger',
            str_contains($s, 'menunggu')    => 'bg-warning',
            default                         => 'bg-secondary',
        };
    }
}

// ------------------------
// Normalisasi data masuk
// ------------------------
$student          = rowa($student ?? []);
$class            = rowa($class ?? []);
$stats            = rowa($stats ?? []);
$recentViolations = is_array($recentViolations ?? null) ? $recentViolations : [];
$upcomingSessions = is_array($upcomingSessions ?? null) ? $upcomingSessions : [];

// Data siswa utama
$studentId   = (int) ($student['id'] ?? 0);
$fullName    = trim((string) ($student['full_name'] ?? 'Siswa'));
$nis         = trim((string) ($student['nis'] ?? ''));
$nisn        = trim((string) ($student['nisn'] ?? ''));
$gender      = $student['gender'] ?? null;
$birthPlace  = $student['birth_place'] ?? null;
$birthDate   = $student['birth_date'] ?? null;
$religion    = $student['religion'] ?? null;
$address     = $student['address'] ?? null;
$status      = $student['status'] ?? 'Aktif';

// Info kelas & akademik
$className      = $student['class_name'] ?? ($class['name'] ?? null);
$gradeLabel     = $student['grade_label'] ?? ($class['grade_label'] ?? null);
$majorName      = $student['major_name'] ?? ($class['major_name'] ?? null);
$academicYear   = $student['academic_year_name'] ?? ($class['academic_year_name'] ?? null);
$admissionDate  = $student['admission_date'] ?? null;

// Info akun (hanya ringkas, tidak ada detail teknis admin)
$email   = $student['email'] ?? null;
$phone   = $student['phone'] ?? null;

// Info orang tua / wali
$parentName  = $student['parent_name'] ?? null;
$parentPhone = $student['parent_phone'] ?? null;
$parentEmail = $student['parent_email'] ?? null;

// Ringkasan pelanggaran (yang boleh diakses Wali Kelas)
$totalPoints = (int) ($student['total_violation_points'] ?? $stats['total_points'] ?? 0);
$totalCases  = (int) ($stats['total_violations'] ?? 0);

// Hitung umur kalau ada tanggal lahir
$ageText = '-';
if (! empty($birthDate)) {
    $ts = strtotime($birthDate);
    if ($ts) {
        $today = new DateTimeImmutable('today');
        $bday  = new DateTimeImmutable(date('Y-m-d', $ts));
        $diff  = $today->diff($bday);
        $ageText = $diff->y . ' tahun';
    } else {
        $ageText = esc($birthDate);
    }
}

// Initial untuk fallback text (kalau dibutuhkan)
$initial = mb_strtoupper(mb_substr($fullName, 0, 1, 'UTF-8'));

// Status badge
$statusBadgeClass = $status === 'Aktif' ? 'bg-success' : 'bg-secondary';

// URL navigasi
$backUrl = base_url('homeroom/my-class');
$createViolationUrl = $studentId
    ? base_url('homeroom/violations/create?student=' . $studentId)
    : null;

// ✅ Default avatar svg (sesuai public/assets/images/users/default-avatar.svg)
$defaultAvatar = base_url('assets/images/users/default-avatar.svg');

/**
 * ✅ Normalisasi foto profil siswa (konsisten dengan halaman admin/users)
 * - Kosong/placeholder/template assets => dianggap tidak ada foto (pakai default-avatar.svg)
 */
$photoRaw  = (string)($student['profile_photo'] ?? '');
$photoTrim = trim($photoRaw);
$photoNorm = strtolower(ltrim(str_replace('\\', '/', $photoTrim), '/'));
$photoBase = strtolower(basename($photoNorm));

$placeholders = [
    'default-avatar.png','default-avatar.jpg','default-avatar.jpeg','default-avatar.svg',
    'avatar.png','avatar.jpg','avatar.jpeg',
    'user.png','user.jpg','user.jpeg',
    'no-image.png','noimage.png','placeholder.png','blank.png',
];

if ($photoTrim === '') {
    $photo = null;
} elseif ((strpos($photoNorm, 'assets/') === 0 || strpos($photoNorm, 'public/assets/') === 0)
    && $photoNorm !== 'assets/images/users/default-avatar.svg'
) {
    $photo = null;
} elseif (in_array($photoBase, $placeholders, true) && $photoNorm !== 'assets/images/users/default-avatar.svg') {
    $photo = null;
} else {
    $photo = $photoTrim;
}

// user_avatar() akan mengembalikan URL yang aman; jika tetap gagal, onerror -> defaultAvatar
$avatarSrc = user_avatar($photo);
?>

<div class="page-content">
    <div class="container-fluid">

        <!-- Header + breadcrumb kecil -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">Detail Siswa</h4>
                <div class="text-muted small">
                    Wali Kelas melihat data untuk
                    <strong><?= esc($fullName) ?></strong>
                    <?php if ($className): ?>
                        · Kelas <?= esc($className) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= $backUrl ?>" class="btn btn-light btn-sm">
                    &larr; Kembali ke Kelas Binaan
                </a>

                <?php if ($createViolationUrl): ?>
                    <!-- Sesuai peran Wali Kelas: boleh mencatat pelanggaran -->
                    <a href="<?= esc($createViolationUrl) ?>" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-alert-plus-outline me-1"></i>Catat Pelanggaran
                    </a>
                <?php endif; ?>
            </div>
            <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/my-class') ?>">Kelas</a></li>
          <li class="breadcrumb-item active">Detail Siswa</li>
        </ol>
      </div>
        </div>

        <!-- Flash message -->
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Kolom kiri: ringkasan siswa + orang tua -->
            <div class="col-lg-4">

                <!-- Card: avatar & identitas utama -->
                <div class="card mb-3">
                    <div class="card-body text-center">

                        <!-- ✅ FOTO PROFIL + FALLBACK default-avatar.svg -->
                        <div class="mx-auto mb-3" style="width: 96px; height: 96px;">
                            <img
                                src="<?= esc($avatarSrc, 'attr') ?>"
                                alt="<?= esc($fullName ?: $initial, 'attr') ?>"
                                class="rounded-circle img-thumbnail"
                                width="96"
                                height="96"
                                loading="lazy"
                                style="object-fit: cover;"
                                onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
                            >
                        </div>

                        <h5 class="mb-1"><?= esc($fullName) ?></h5>

                        <p class="text-muted mb-1">
                            <?php if ($nisn !== '' || $nis !== ''): ?>
                                NIS/NISN:
                                <?= esc($nis !== '' ? $nis : '-') ?> /
                                <?= esc($nisn !== '' ? $nisn : '-') ?>
                            <?php else: ?>
                                NIS/NISN belum terisi
                            <?php endif; ?>
                        </p>

                        <?php if ($className): ?>
                            <p class="mb-2">
                                <span class="badge bg-soft-primary text-primary">
                                    <i class="mdi mdi-school me-1"></i>
                                    <?= esc($className) ?>
                                </span>
                            </p>
                        <?php endif; ?>

                        <span class="badge <?= $statusBadgeClass ?> px-3 py-1">
                            <?= esc($status ?: 'Status tidak diketahui') ?>
                        </span>

                        <hr>

                        <!-- Ringkasan poin pelanggaran -->
                        <div class="d-flex justify-content-center gap-3">
                            <div>
                                <small class="text-muted d-block">Total Kasus</small>
                                <span class="fw-bold"><?= (int) $totalCases ?></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Total Poin</small>
                                <span class="badge <?= badge_points_class($totalPoints) ?> font-size-14">
                                    <?= (int) $totalPoints ?> poin
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: info kontak siswa -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="mdi mdi-phone-outline me-2"></i>Kontak Siswa
                        </h5>

                        <div class="mb-2">
                            <small class="text-muted d-block">No. HP</small>

                            <?php if (!empty($phone)): ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <a href="tel:<?= esc($phone) ?>" class="me-1">
                                        <?= esc($phone) ?>
                                    </a>

                                    <?= view('components/wa_button', [
                                        'phone' => $phone,
                                        'label' => 'WhatsApp',
                                        'class' => 'btn btn-sm btn-success',
                                    ]) ?>
                                </div>
                            <?php else: ?>
                                <span class="fw-medium">-</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-0">
                            <small class="text-muted d-block">Email</small>
                            <span class="fw-medium">
                                <?= ! empty($email) ? esc($email) : '-' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card: info orang tua / wali -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="mdi mdi-account-child-outline me-2"></i>Orang Tua / Wali
                        </h5>

                        <div class="mb-2">
                            <small class="text-muted d-block">Nama</small>
                            <span class="fw-medium">
                                <?= ! empty($parentName) ? esc($parentName) : 'Belum terhubung di sistem' ?>
                            </span>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">No. HP</small>

                            <?php if (!empty($parentPhone)): ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <a href="tel:<?= esc($parentPhone) ?>" class="me-1">
                                        <?= esc($parentPhone) ?>
                                    </a>

                                    <?= view('components/wa_button', [
                                        'phone' => $parentPhone,
                                        'label' => 'WhatsApp',
                                        'class' => 'btn btn-sm btn-success',
                                    ]) ?>
                                </div>
                            <?php else: ?>
                                <span class="fw-medium">-</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Email</small>
                            <span class="fw-medium">
                                <?= ! empty($parentEmail) ? esc($parentEmail) : '-' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card: info singkat kelas -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="mdi mdi-account-multiple-outline me-2"></i>Kelas Perwalian
                        </h5>

                        <div class="mb-2">
                            <small class="text-muted d-block">Kelas</small>
                            <span class="fw-medium">
                                <?= ! empty($className) ? esc($className) : '-' ?>
                            </span>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Tingkat / Jurusan</small>
                            <span class="fw-medium">
                                <?php
                                $parts = [];
                                if (! empty($gradeLabel)) {
                                    $parts[] = $gradeLabel;
                                }
                                if (! empty($majorName)) {
                                    $parts[] = $majorName;
                                }
                                echo ! empty($parts) ? esc(implode(' · ', $parts)) : '-';
                                ?>
                            </span>
                        </div>

                        <div class="mb-0">
                            <small class="text-muted d-block">Tahun Ajaran</small>
                            <span class="fw-medium">
                                <?= ! empty($academicYear) ? esc($academicYear) : '-' ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kolom kanan: detail biodata, akademik, ringkasan pelanggaran -->
            <div class="col-lg-8">
                <div class="row">
                    <!-- Informasi personal -->
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="mdi mdi-account me-2"></i>Informasi Personal
                                </h5>

                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted" style="width: 40%;">
                                                    <i class="mdi mdi-account me-1"></i>Nama Lengkap
                                                </td>
                                                <td class="fw-medium"><?= esc($fullName) ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-gender-male-female me-1"></i>Jenis Kelamin
                                                </td>
                                                <td class="fw-medium">
                                                    <?php
                                                    if ($gender === 'L') {
                                                        echo 'Laki-laki';
                                                    } elseif ($gender === 'P') {
                                                        echo 'Perempuan';
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-map-marker-outline me-1"></i>Tempat Lahir
                                                </td>
                                                <td class="fw-medium">
                                                    <?= ! empty($birthPlace) ? esc($birthPlace) : '-' ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-calendar me-1"></i>Tanggal Lahir
                                                </td>
                                                <td class="fw-medium">
                                                    <?= fmt_date_id($birthDate) ?>
                                                    <?php if ($ageText !== '-'): ?>
                                                        <span class="text-muted"> · <?= esc($ageText) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-book-cross me-1"></i>Agama
                                                </td>
                                                <td class="fw-medium">
                                                    <?= ! empty($religion) ? esc($religion) : '-' ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted align-top">
                                                    <i class="mdi mdi-home-outline me-1"></i>Alamat
                                                </td>
                                                <td class="fw-medium">
                                                    <?= ! empty($address) ? nl2br(esc($address)) : '-' ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Informasi akademik -->
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="mdi mdi-school-outline me-2"></i>Informasi Akademik
                                </h5>

                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted" style="width: 40%;">
                                                    <i class="mdi mdi-numeric me-1"></i>NIS
                                                </td>
                                                <td class="fw-medium">
                                                    <?= $nis !== '' ? esc($nis) : '-' ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-card-account-details-outline me-1"></i>NISN
                                                </td>
                                                <td class="fw-medium">
                                                    <?= $nisn !== '' ? esc($nisn) : '-' ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-school me-1"></i>Kelas
                                                </td>
                                                <td class="fw-medium">
                                                    <?= ! empty($className) ? esc($className) : '-' ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-calendar-range me-1"></i>Tahun Ajaran
                                                </td>
                                                <td class="fw-medium">
                                                    <?= ! empty($academicYear) ? esc($academicYear) : '-' ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-calendar-plus me-1"></i>Tanggal Masuk
                                                </td>
                                                <td class="fw-medium">
                                                    <?= fmt_date_id($admissionDate) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">
                                                    <i class="mdi mdi-check-circle-outline me-1"></i>Status
                                                </td>
                                                <td class="fw-medium">
                                                    <span class="badge <?= $statusBadgeClass ?>">
                                                        <?= esc($status ?: 'Status tidak diketahui') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Pelanggaran Terbaru (hanya yang tidak soft delete) -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="card-title mb-0">
                                    <i class="mdi mdi-alert-octagram-outline me-2"></i>Pelanggaran Terbaru
                                </h5>
                            </div>
                        </div>

                        <?php
                        // Filter di sisi view sebagai pengaman tambahan:
                        // skip record yang punya deleted_at tidak null
                        $rows = [];
                        foreach ($recentViolations as $rv) {
                            $vRow = rowa($rv);
                            if (! empty($vRow['deleted_at'])) {
                                continue;
                            }
                            $rows[] = $vRow;
                        }
                        ?>

                        <?php if (empty($rows)): ?>
                            <div class="text-center py-4">
                                <i class="mdi mdi-check-circle-outline text-success mb-2" style="font-size: 32px;"></i>
                                <p class="text-muted mb-0">
                                    Belum ada pelanggaran aktif yang tercatat.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 15%;">Tanggal</th>
                                            <th style="width: 30%;">Kategori</th>
                                            <th style="width: 15%;">Tingkat</th>
                                            <th style="width: 10%;">Poin</th>
                                            <th style="width: 15%;">Status</th>
                                            <th style="width: 15%;">Dicatat Oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $rv): ?>
                                            <?php
                                            $tgl        = $rv['violation_date'] ?? $rv['date'] ?? null;
                                            $catName    = $rv['category_name'] ?? $rv['violation_category_name'] ?? '-';
                                            $severity   = $rv['severity_level'] ?? $rv['severity'] ?? '-';
                                            $points     = (int) ($rv['points'] ?? $rv['violation_points'] ?? 0);
                                            $vStatus    = $rv['status'] ?? '-';
                                            $reportedBy = $rv['reporter_name'] ?? $rv['reported_by_name'] ?? '-';
                                            ?>
                                            <tr>
                                                <td><?= fmt_date_id($tgl) ?></td>
                                                <td><?= esc($catName) ?></td>
                                                <td>
                                                    <span class="badge <?= badge_severity_class($severity) ?>">
                                                        <?= esc($severity ?: '-') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-danger"><?= $points ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= badge_status_class($vStatus) ?>">
                                                        <?= esc($vStatus ?: '-') ?>
                                                    </span>
                                                </td>
                                                <td><?= esc($reportedBy ?: '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Jadwal konseling mendatang (jika disediakan dari controller) -->
                <?php if (! empty($upcomingSessions)): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="mdi mdi-calendar-clock-outline me-2"></i>Jadwal Konseling Mendatang
                            </h5>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 18%;">Tanggal</th>
                                            <th style="width: 15%;">Jam</th>
                                            <th>Topik</th>
                                            <th style="width: 22%;">Guru BK</th>
                                            <th style="width: 15%;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingSessions as $s): ?>
                                            <?php
                                            $row     = rowa($s);
                                            $sd      = $row['session_date'] ?? null;
                                            $st      = $row['session_time'] ?? null;
                                            $topic   = $row['topic'] ?? $row['title'] ?? '-';
                                            $cName   = $row['counselor_name'] ?? '-';
                                            $sStatus = $row['status_label'] ?? $row['status'] ?? 'Dijadwalkan';
                                            ?>
                                            <tr>
                                                <td><?= fmt_date_id($sd) ?></td>
                                                <td><?= ! empty($st) ? esc(substr($st, 0, 5)) : '-' ?></td>
                                                <td><?= esc($topic) ?></td>
                                                <td><?= esc($cName) ?></td>
                                                <td>
                                                    <span class="badge badge-soft-primary">
                                                        <?= esc($sStatus) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-muted small mt-2 mb-0">
                                Wali Kelas hanya dapat melihat jadwal konseling, tanpa isi catatan konseling
                                yang bersifat rahasia (sesuai perancangan sistem).
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>
