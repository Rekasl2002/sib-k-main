<!-- app/Views/student/staff.php -->
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
helper(['url', 'form', 'phone']); // phone -> wa_url() dipakai komponen wa_button

// Normalisasi variabel dari controller
$student   = $student   ?? [];
$class     = $class     ?? null;
$homeroom  = $homeroom  ?? null;
$counselor = $counselor ?? null;

if (!function_exists('h')) {
    function h($v) { return esc($v ?? ''); }
}

if (!function_exists('avatar_url_safe')) {
    function avatar_url_safe(?string $photo): string
    {
        helper('url');
        $default = base_url('assets/images/users/default-avatar.svg');

        $raw = trim((string)($photo ?? ''));
        if ($raw === '') return $default;

        $rawNoQ = (string) strtok($raw, '?');
        $rawNoQ = trim($rawNoQ);
        if ($rawNoQ === '') return $default;

        // URL penuh
        if (preg_match('~^https?://~i', $rawNoQ)) return $rawNoQ;

        // Normalisasi slash
        $rel  = ltrim(str_replace('\\', '/', $rawNoQ), '/');
        $base = strtolower(basename($rel));

        // Placeholder umum
        $placeholders = [
            'default-avatar.png','default-avatar.jpg','default-avatar.jpeg','default-avatar.svg',
            'avatar.png','avatar.jpg','avatar.jpeg',
            'user.png','user.jpg','user.jpeg',
            'no-image.png','noimage.png','placeholder.png','blank.png',
        ];

        // Kalau file placeholder selain default svg kita, pakai default
        if (in_array($base, $placeholders, true) && $rel !== 'assets/images/users/default-avatar.svg') {
            return $default;
        }

        // Kalau menunjuk assets template (selain default svg), anggap kosong
        if ((strpos(strtolower($rel), 'assets/') === 0 || strpos(strtolower($rel), 'public/assets/') === 0)
            && $rel !== 'assets/images/users/default-avatar.svg') {
            return $default;
        }

        return base_url($rel);
    }
}

$studentName = $student['full_name'] ?? '—';

$classLabel = '';
if (is_array($class)) {
    $classLabel = trim(($class['class_name'] ?? '') . ' ' . ($class['major'] ?? ''));
} else {
    $classLabel = trim(($student['class_name'] ?? '') . ' ' . ($student['major'] ?? ''));
}
$classLabel = $classLabel !== '' ? $classLabel : '—';

$defaultAvatar = base_url('assets/images/users/default-avatar.svg');

if (!function_exists('staff_card')) {
    function staff_card(string $title, ?array $u, string $studentName, string $classLabel)
    {
        $name  = $u['full_name'] ?? null;
        $email = $u['email'] ?? null;
        $phone = $u['phone'] ?? null;

        $avatar = avatar_url_safe($u['profile_photo'] ?? null);

        $isActive  = isset($u['is_active']) ? (int) $u['is_active'] : null;
        $badge     = $isActive === 1 ? 'bg-success' : ($isActive === 0 ? 'bg-danger' : 'bg-secondary');
        $badgeText = $isActive === 1 ? 'Aktif' : ($isActive === 0 ? 'Nonaktif' : '—');

        // ✅ Mode Siswa (bukan orang tua)
        $waText = "Halo, saya {$studentName} (kelas {$classLabel}). Saya ingin bertanya/koordinasi.";
        ?>
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <img
                        src="<?= esc($avatar, 'attr') ?>"
                        class="rounded-circle"
                        width="56"
                        height="56"
                        alt="<?= esc($title, 'attr') ?>"
                        loading="lazy"
                        style="object-fit:cover;"
                        onerror="this.onerror=null;this.src='<?= esc(base_url('assets/images/users/default-avatar.svg'), 'attr') ?>';"
                    >
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="mb-1"><?= esc($title) ?></h6>
                            <span class="badge <?= esc($badge, 'attr') ?>"><?= esc($badgeText) ?></span>
                        </div>

                        <?php if (!$name): ?>
                            <div class="text-muted">Belum ditetapkan.</div>
                        <?php else: ?>
                            <div class="fw-semibold"><?= esc($name) ?></div>

                            <div class="mt-2 small text-muted">
                                <?php if (!empty($email)): ?>
                                    <div>Email: <a href="mailto:<?= esc($email, 'attr') ?>"><?= esc($email) ?></a></div>
                                <?php else: ?>
                                    <div>Email: —</div>
                                <?php endif; ?>

                                <?php if (!empty($phone)): ?>
                                    <div>Telepon: <a href="tel:<?= esc($phone, 'attr') ?>"><?= esc($phone) ?></a></div>
                                <?php else: ?>
                                    <div>Telepon: —</div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <?= view('components/wa_button', [
                                    'phone' => $phone,
                                    'label' => 'WhatsApp',
                                    'class' => 'btn btn-outline-success btn-sm',
                                    'text'  => $waText,
                                ]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($name): ?>
                    <hr class="my-3">
                    <div class="small text-muted">
                        Tips: Saat menghubungi, sebutkan nama dan kelas kamu.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?>

<!-- Title / Breadcrumb -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Info Guru BK & Wali Kelas</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= route_to('student.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Info Guru</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Header Siswa -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <img
                    src="<?= esc(avatar_url_safe($student['profile_photo'] ?? null), 'attr') ?>"
                    class="rounded-circle"
                    width="56"
                    height="56"
                    alt="<?= esc($studentName, 'attr') ?>"
                    loading="lazy"
                    style="object-fit:cover;"
                    onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
                >
                <div>
                    <div class="fw-semibold"><?= h($studentName) ?></div>
                    <div class="text-muted small">Kelas: <?= h($classLabel) ?></div>
                    <div class="text-muted small">NIS: <?= h($student['nis'] ?? '—') ?> | NISN: <?= h($student['nisn'] ?? '—') ?></div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a class="btn btn-outline-secondary btn-sm" href="<?= route_to('student.dashboard') ?>">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Cards Guru -->
<div class="row g-3">
    <div class="col-lg-6">
        <?php staff_card('Guru BK', is_array($counselor) ? $counselor : null, $studentName, $classLabel); ?>
    </div>
    <div class="col-lg-6">
        <?php staff_card('Wali Kelas', is_array($homeroom) ? $homeroom : null, $studentName, $classLabel); ?>
    </div>
</div>

<?= $this->endSection() ?>
