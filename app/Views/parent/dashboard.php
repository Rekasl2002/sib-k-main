<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
use CodeIgniter\I18n\Time;

// ✅ tambah helper auth supaya bisa pakai user_avatar() (konsisten dengan halaman lain)
helper(['url', 'auth']);

function h($v){ return esc($v ?? ''); }
function dt($d,$t=null){
  if(!$d) return '-';
  try {
    $x = Time::parse($d);
    $s = $x->toLocalizedString('dd MMM yyyy');
    if ($t) $s .= ' ' . h($t);
    return $s;
  } catch (\Throwable $e) {
    return h($d);
  }
}
function badgeClass($status){
  return match((string)$status){
    'Dijadwalkan' => 'bg-info',
    'Selesai'     => 'bg-success',
    'Dibatalkan'  => 'bg-secondary',
    'Ditunda'     => 'bg-warning',
    'Tidak Hadir' => 'bg-danger',
    default       => 'bg-light text-dark',
  };
}

// Normalisasi variabel dari controller
$children         = $children ?? [];
$stats            = $stats ?? null; // ['children','violations_total','points_total','upcoming_sessions']
$violationTotal   = isset($violationTotal) ? (int)$violationTotal : (int)($stats['violations_total'] ?? 0);
$upcoming         = $upcoming ?? [];
$recentViolations = $recentViolations ?? [];

// Peta id anak -> nama (untuk fallback di widget)
$childNameMap = [];
foreach ($children as $c) { $childNameMap[$c['id']] = $c['full_name'] ?? ('Siswa #'.$c['id']); }

// Deteksi kolom opsional per anak (jika controller menyiapkan)
$hasViolCount = array_reduce($children, fn($carry,$r)=>$carry || isset($r['violations_count']), false);
$hasPoints    = array_reduce($children, fn($carry,$r)=>$carry || isset($r['points_sum']), false);
$hasUpcoming  = array_reduce($children, fn($carry,$r)=>$carry || isset($r['upcoming_sessions']), false);

// ✅ Default avatar svg (sesuai public/assets/images/users/default-avatar.svg)
$defaultAvatar = base_url('assets/images/users/default-avatar.svg');

// ✅ Avatar helper (robust + konsisten): kosong/placeholder/template -> default svg
function avatar_url($row): string {
  $defaultAvatar = base_url('assets/images/users/default-avatar.svg');

  $photoRaw  = (string)($row['profile_photo'] ?? '');
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
  }
  // jika menunjuk ke assets/ (avatar template) → dianggap tidak ada foto (kecuali default-avatar.svg kita)
  elseif ((strpos($photoNorm, 'assets/') === 0 || strpos($photoNorm, 'public/assets/') === 0)
      && $photoNorm !== 'assets/images/users/default-avatar.svg'
  ) {
    $photo = null;
  }
  // jika filename placeholder → dianggap tidak ada foto (kecuali default-avatar.svg kita)
  elseif (in_array($photoBase, $placeholders, true) && $photoNorm !== 'assets/images/users/default-avatar.svg') {
    $photo = null;
  } else {
    $photo = $photoTrim;
  }

  // gunakan user_avatar() jika tersedia; fallback aman jika helper berubah
  if (function_exists('user_avatar')) {
    $src = user_avatar($photo);
    return $src ?: $defaultAvatar;
  }

  return $photo ? base_url($photo) : $defaultAvatar;
}
?>

    <!-- Title / Breadcrumb -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
          <h4 class="mb-sm-0">Dashboard ORANG TUA</h4>
          <div class="page-title-right">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= base_url() ?>">Halaman Utama Web</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

  <!-- Quick Stats -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Total Anak Terdaftar</div>
          <div class="display-6"><?= (int)($stats['children'] ?? count($children)) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Total Pelanggaran (Semua Anak)</div>
          <div class="display-6"><?= $violationTotal ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Total Poin (Semua Anak)</div>
          <div class="display-6"><?= (int)($stats['points_total'] ?? 0) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Sesi Konseling Mendatang</div>
          <div class="display-6"><?= (int)($stats['upcoming_sessions'] ?? count($upcoming)) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Daftar Anak -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h6 class="mb-3">Daftar Anak</h6>

      <?php if (empty($children)): ?>
        <div class="text-muted">Belum ada data anak terhubung.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width:42px;"></th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>NIS</th>
                <th>NISN</th>
                <?php if ($hasViolCount): ?><th class="text-center">Pelanggaran</th><?php endif; ?>
                <?php if ($hasPoints): ?><th class="text-center">Poin</th><?php endif; ?>
                <?php if ($hasUpcoming): ?><th class="text-center">Sesi</th><?php endif; ?>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($children as $c): ?>
              <?php $childName = $c['full_name'] ?? '—'; ?>
              <tr>
                <td>
                  <img
                    src="<?= esc(avatar_url($c), 'attr') ?>"
                    class="rounded-circle"
                    width="36"
                    height="36"
                    alt="<?= esc($childName, 'attr') ?>"
                    loading="lazy"
                    style="object-fit:cover;"
                    onerror="this.onerror=null;this.src='<?= esc($defaultAvatar, 'attr') ?>';"
                  >
                </td>
                <td class="fw-semibold"><?= h($childName) ?></td>
                <td><?= h($c['class_name'] ?? '—') ?></td>
                <td><?= h($c['nis'] ?? '—') ?></td>
                <td><?= h($c['nisn'] ?? '—') ?></td>
                <?php if ($hasViolCount): ?>
                  <td class="text-center"><?= (int)($c['violations_count'] ?? 0) ?></td>
                <?php endif; ?>
                <?php if ($hasPoints): ?>
                  <td class="text-center"><?= (int)($c['points_sum'] ?? 0) ?></td>
                <?php endif; ?>
                <?php if ($hasUpcoming): ?>
                  <td class="text-center"><?= (int)($c['upcoming_sessions'] ?? 0) ?></td>
                <?php endif; ?>
                <td class="text-end">
                  <div class="btn-group">
                    <a class="btn btn-outline-primary btn-sm" href="<?= route_to('parent.children.profile', $c['id']) ?>">Profil</a>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= route_to('parent.children.violations', $c['id']) ?>">Pelanggaran</a>
                    <a class="btn btn-outline-success btn-sm" href="<?= route_to('parent.children.sessions', $c['id']) ?>">Sesi</a>
                    <a class="btn btn-outline-info btn-sm" href="<?= route_to('parent.children.staff', $c['id']) ?>">Info Guru</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3">
    <!-- Jadwal Konseling Mendatang -->
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="mb-3">Jadwal Konseling Mendatang</h6>
          <?php if (empty($upcoming)): ?>
            <div class="text-muted">Belum ada jadwal.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Tanggal</th><th>Topik</th><th>Anak</th><th>Status</th><th>Lokasi</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $u): ?>
                  <tr>
                    <td><?= dt($u['session_date'] ?? null, $u['session_time'] ?? null) ?></td>
                    <td><?= h($u['topic'] ?? '-') ?></td>
                    <td>
                      <?php
                        // Prefer data langsung dari controller; fallback ke peta anak
                        $sid   = $u['student_id'] ?? null;
                        $sname = $u['full_name']  ?? ($sid ? ($childNameMap[$sid] ?? null) : null);
                      ?>
                      <?php if ($sid): ?>
                        <a href="<?= route_to('parent.children.sessions', (int)$sid) ?>"><?= h($sname ?? '—') ?></a>
                      <?php else: ?>
                        <?= h($sname ?? '—') ?>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge <?= badgeClass($u['status'] ?? null) ?>"><?= h($u['status'] ?? '-') ?></span></td>
                    <td><?= h($u['location'] ?? '—') ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Pelanggaran Terbaru -->
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="mb-3">Pelanggaran Terbaru</h6>
          <?php if (empty($recentViolations)): ?>
            <div class="text-muted">Belum ada data pelanggaran.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Tanggal</th><th>Kategori</th><th>Poin</th><th>Anak</th></tr></thead>
                <tbody>
                <?php foreach ($recentViolations as $rv): ?>
                  <tr>
                    <td><?= dt($rv['violation_date'] ?? null) ?></td>
                    <td><?= h($rv['category_name'] ?? '-') ?></td>
                    <td><?= (int)($rv['points'] ?? 0) ?></td>
                    <td>
                      <?php
                        $sid   = $rv['student_id'] ?? null;
                        $sname = $rv['full_name']  ?? ($sid ? ($childNameMap[$sid] ?? null) : null);
                      ?>
                      <?php if ($sid): ?>
                        <a href="<?= route_to('parent.children.violations', (int)$sid) ?>"><?= h($sname ?? '—') ?></a>
                      <?php else: ?>
                        <?= h($sname ?? '—') ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
