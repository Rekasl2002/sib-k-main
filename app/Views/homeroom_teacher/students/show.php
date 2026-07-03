<!-- app/Views/homeroom_teacher/students/show.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Load helper global untuk nomor HP / WhatsApp
helper('phone');
// ✅ Pakai helper auth agar konsisten dengan halaman lain (user_avatar)
helper('auth');
helper('permission');

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
$upcomingSessions = is_array($upcomingSessions ?? null) ? $upcomingSessions : [];

// Data siswa utama
$studentId   = (int) ($student['id'] ?? 0);
$fullName    = trim((string) ($student['full_name'] ?? 'Siswa'));
$nisn        = trim((string) ($student['nisn'] ?? ''));
$nik         = trim((string) ($student['nik'] ?? ''));
$gender      = $student['gender'] ?? null;
$birthPlace  = $student['birth_place'] ?? null;
$birthDate   = $student['birth_date'] ?? null;
$religion    = $student['religion'] ?? null;
$address     = $student['address'] ?? null;
$status      = $student['status'] ?? 'Aktif';
$specialNeeds = $student['special_needs'] ?? null;
$disability = $student['disability'] ?? null;
$kipPipNumber = $student['kip_pip_number'] ?? null;
$fatherName = $student['father_name'] ?? null;
$motherName = $student['mother_name'] ?? null;
$guardianName = $student['guardian_name'] ?? null;

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
$parentId    = (int) ($student['parent_id'] ?? 0);

// Ringkasan pendampingan siswa (yang boleh diakses Wali Kelas)

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

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Detail Siswa</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('homeroom/my-class') ?>">Kelas Binaan</a></li>
                    <li class="breadcrumb-item active">Detail Siswa</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mb-3">
    <div class="col-12 d-flex gap-2 flex-wrap">
        <a href="<?= $backUrl ?>" class="btn btn-secondary btn-sm">
            <i class="mdi mdi-arrow-left me-1"></i>Kembali ke Kelas Binaan
        </a>
        <?php if (function_exists('has_permission') && has_permission('manage_students')): ?>
            <a href="<?= base_url('homeroom/students/edit/' . $studentId) ?>" class="btn btn-primary btn-sm">
                <i class="mdi mdi-pencil me-1"></i>Edit Siswa
            </a>
            <?php if ($parentId > 0): ?>
                <a href="<?= base_url('homeroom/parents/' . $parentId) ?>" class="btn btn-outline-info btn-sm">
                    <i class="mdi mdi-account-supervisor me-1"></i>Akun Orang Tua
                </a>
            <?php endif; ?>
            <form method="post" action="<?= base_url('homeroom/students/delete/' . $studentId) ?>" class="d-inline" onsubmit="return confirm('Hapus siswa ini dari kelas binaan?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="mdi mdi-delete me-1"></i>Hapus
                </button>
            </form>
        <?php endif; ?>
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
                            <?php if ($nisn !== ''): ?>
                                NISN:
                                <?= esc($nisn !== '' ? $nisn : '-') ?>
                            <?php else: ?>
                                NISN belum terisi
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

                        <?php if ($parentId > 0): ?>
                            <a href="<?= base_url('homeroom/parents/' . $parentId) ?>" class="btn btn-sm btn-outline-primary">
                                Lihat Profil Orang Tua
                            </a>
                        <?php elseif (function_exists('has_permission') && has_permission('manage_students')): ?>
                            <a href="<?= base_url('homeroom/students/edit/' . $studentId) ?>" class="btn btn-sm btn-outline-secondary">
                                Hubungkan Akun Orang Tua
                            </a>
                        <?php endif; ?>
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

            <!-- Kolom kanan: detail biodata, akademik, dan pendampingan siswa -->
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
                                            <tr>
                                                <td class="text-muted"><i class="mdi mdi-account-heart me-1"></i>Kebutuhan Khusus</td>
                                                <td class="fw-medium"><?= ! empty($specialNeeds) ? esc($specialNeeds) : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><i class="mdi mdi-wheelchair-accessibility me-1"></i>Disabilitas</td>
                                                <td class="fw-medium"><?= ! empty($disability) ? esc($disability) : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><i class="mdi mdi-soccer me-1"></i>Hobi</td>
                                                <td class="fw-medium"><?= ! empty($student['hobi']) ? esc($student['hobi']) : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><i class="mdi mdi-account-group me-1"></i>Ekstrakurikuler / Organisasi</td>
                                                <td class="fw-medium"><?= ! empty($student['ekskul_organisasi']) ? esc($student['ekskul_organisasi']) : '-' ?></td>
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
                                                    <i class="mdi mdi-numeric me-1"></i>NIK
                                                </td>
                                                <td class="fw-medium">
                                                    <?= $nik !== '' ? esc($nik) : '-' ?>
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
                                                    <i class="mdi mdi-card-bulleted-outline me-1"></i>Nomor KIP/PIP
                                                </td>
                                                <td class="fw-medium">
                                                    <?= ! empty($kipPipNumber) ? esc($kipPipNumber) : '-' ?>
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
                                            <tr>
                                                <td class="text-muted"><i class="mdi mdi-account-tie me-1"></i>Nama Ayah Kandung</td>
                                                <td class="fw-medium"><?= ! empty($fatherName) ? esc($fatherName) : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><i class="mdi mdi-account-heart-outline me-1"></i>Nama Ibu Kandung</td>
                                                <td class="fw-medium"><?= ! empty($motherName) ? esc($motherName) : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><i class="mdi mdi-account-supervisor me-1"></i>Nama Wali</td>
                                                <td class="fw-medium"><?= ! empty($guardianName) ? esc($guardianName) : '-' ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>

<?= $this->endSection() ?>
