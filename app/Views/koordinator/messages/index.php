<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/{peran}/messages/index.php
 * Fitur: Pesan Internal — HALAMAN UTAMA (ala media sosial / WhatsApp).
 * Identik untuk semua peran; data & izin dikirim dari controller per peran.
 *
 * Menampilkan daftar percakapan + tombol mulai percakapan baru + mode pilih & hapus.
 */
helper(['url']);

$basePath  = trim((string)($basePath ?? ''), '/');
$msgBase   = site_url($basePath . '/messages');
$defaultAvatar = base_url('assets/images/users/default-avatar.svg');

/** Warna lingkaran inisial per peran (cadangan bila tak ada foto). */
$roleColor = static function (int $roleId): string {
    return [1 => '#556ee6', 2 => '#34c38f', 3 => '#50a5f1', 4 => '#f1b44c', 5 => '#6f42c1', 6 => '#e83e8c'][$roleId] ?? '#74788d';
};

/** Sumber foto profil yang aman; '' bila tak ada (akan pakai inisial). */
$photoSrc = static function (array $info): string {
    $photo = trim((string)($info['profile_photo'] ?? ''));
    if ($photo === '') return '';
    $photoNoQ = trim((string) strtok($photo, '?'));
    if ($photoNoQ === '') return '';
    if (preg_match('~^https?://~i', $photoNoQ)) return $photoNoQ;
    if (strpos($photoNoQ, '/') !== false || strpos($photoNoQ, '\\') !== false) {
        return base_url(ltrim(str_replace('\\', '/', $photoNoQ), '/'));
    }
    $uid = (int)($info['id'] ?? 0);
    if ($uid > 0) return base_url("uploads/profile_photos/{$uid}/{$photoNoQ}");
    return '';
};

/** Render lingkaran avatar (foto bila ada, jika tidak inisial berwarna). */
$avatar = function (array $info, int $size) use ($photoSrc, $roleColor): string {
    $src  = $photoSrc($info);
    $name = trim((string)($info['full_name'] ?? '?'));
    if ($src !== '') {
        return '<img src="' . esc($src, 'attr') . '" class="rounded-circle" '
            . 'style="width:' . $size . 'px;height:' . $size . 'px;object-fit:cover;" '
            . 'alt="' . esc($name, 'attr') . '">';
    }
    $parts = preg_split('/\s+/', $name);
    $ini   = strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    $bg    = $roleColor((int)($info['role_id'] ?? 0));
    return '<span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-semibold" '
        . 'style="width:' . $size . 'px;height:' . $size . 'px;background:' . $bg . ';font-size:' . (int)($size * 0.4) . 'px;">'
        . esc($ini) . '</span>';
};

$totalUnread = 0;
foreach (($conversations ?? []) as $c) {
    $totalUnread += (int)($c['unread'] ?? 0);
}
?>

<!-- Page Title -->
<div class="row">
  <div class="col-12">
    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0"><i class="mdi mdi-chat-outline me-2"></i>Pesan</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url($basePath . '/dashboard') ?>"><?= esc($roleLabel ?? 'Beranda') ?></a></li>
          <li class="breadcrumb-item active">Pesan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="mdi mdi-check-circle me-2"></i><?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-dark">
          <i class="mdi mdi-message-text-outline me-2"></i>Daftar Percakapan
          <?php if ($totalUnread > 0): ?>
            <span class="badge bg-danger ms-1" id="listUnreadBadge"><?= (int)$totalUnread ?> baru</span>
          <?php endif; ?>
        </h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newChatModal">
            <i class="mdi mdi-plus me-1"></i> Pesan Baru
          </button>
          <button type="button" class="btn btn-outline-danger" id="toggleDeleteBtn">
            <i class="mdi mdi-trash-can-outline me-1"></i> Pilih &amp; Hapus
          </button>
          <form method="post" action="<?= site_url($basePath . '/messages/delete-all') ?>" id="deleteAllForm" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger" id="deleteAllBtn">
              <i class="mdi mdi-trash-can me-1"></i> Hapus Semua
            </button>
          </form>
        </div>
      </div>

      <div class="card-body">
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
            <input type="text" class="form-control" id="convSearch" placeholder="Cari percakapan berdasarkan nama...">
          </div>
        </div>

        <form method="post" action="<?= site_url($basePath . '/messages/delete') ?>" id="deleteForm">
          <?= csrf_field() ?>

          <div class="d-none mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="deleteBar">
            <div class="d-flex align-items-center gap-3">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="selectAllCb">
                <label class="form-check-label text-dark fw-semibold" for="selectAllCb">Pilih Semua</label>
              </div>
              <span class="text-dark text-muted">(<span id="selCount">0</span> dipilih)</span>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-light btn-sm" id="cancelDeleteBtn">Batal</button>
              <button type="submit" class="btn btn-danger btn-sm" id="confirmDeleteBtn" disabled>
                <i class="mdi mdi-trash-can-outline me-1"></i> Hapus Terpilih
              </button>
            </div>
          </div>

          <div class="list-group list-group-flush" id="convList">
            <?php if (empty($conversations)): ?>
              <div class="text-center py-5" id="emptyState">
                <i class="mdi mdi-chat-remove-outline text-dark" style="font-size:48px;"></i>
                <p class="text-dark mt-2 mb-0">Belum ada percakapan. Mulai dengan tombol <strong>Pesan Baru</strong>.</p>
              </div>
            <?php else: ?>
              <?php foreach ($conversations as $c): ?>
                <?php $unread = (int)($c['unread'] ?? 0); ?>
                <div class="list-group-item conv-item px-2 <?= $unread > 0 ? 'bg-light' : '' ?>"
                     data-name="<?= esc(strtolower((string)$c['full_name']), 'attr') ?>"
                     data-cid="<?= (int)$c['id'] ?>"
                     data-href="<?= site_url($basePath . '/messages/chat/' . (int)$c['other_id']) ?>">
                  <div class="d-flex align-items-center gap-2">
                    <div class="form-check conv-check d-none">
                      <input class="form-check-input conv-cb" type="checkbox" name="conversation_ids[]" value="<?= (int)$c['id'] ?>">
                    </div>
                    <div class="flex-shrink-0"><?= $avatar($c, 48) ?></div>
                    <div class="flex-grow-1 min-w-0" style="min-width:0;">
                      <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark text-truncate <?= $unread > 0 ? 'fw-bold' : '' ?>"><?= esc($c['full_name']) ?></h6>
                        <small class="text-dark text-nowrap ms-2"><?= esc($c['time'] ?? '') ?></small>
                      </div>
                      <div class="small text-dark text-truncate"><?= esc($c['subtitle'] ?? '') ?></div>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-dark text-truncate conv-preview"><?= esc($c['preview'] ?? '') ?></span>
                        <span class="badge bg-danger rounded-pill ms-2 conv-badge <?= $unread > 0 ? '' : 'd-none' ?>"><?= $unread ?></span>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Pesan Baru -->
<div class="modal fade" id="newChatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="mdi mdi-account-plus me-2"></i>Mulai Percakapan Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
          <input type="text" class="form-control" id="recipientSearch" placeholder="Cari nama atau peran pengguna...">
        </div>
        <div class="list-group" id="recipientList">
          <?php if (empty($recipients)): ?>
            <div class="text-center py-4 text-dark">Tidak ada pengguna yang dapat dikirimi pesan.</div>
          <?php else: ?>
            <?php foreach ($recipients as $r): ?>
              <a href="<?= site_url($basePath . '/messages/chat/' . (int)$r['id']) ?>"
                 class="list-group-item list-group-item-action d-flex align-items-center gap-2 recipient-item"
                 data-name="<?= esc(strtolower((string)$r['full_name'] . ' ' . (string)$r['subtitle']), 'attr') ?>">
                <div class="flex-shrink-0"><?= $avatar($r, 40) ?></div>
                <div class="flex-grow-1" style="min-width:0;">
                  <h6 class="mb-0 text-dark text-truncate"><?= esc($r['full_name']) ?></h6>
                  <small class="text-dark text-truncate d-block"><?= esc($r['subtitle'] ?? '') ?></small>
                </div>
                <i class="mdi mdi-chevron-right text-dark"></i>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="text-center py-3 d-none text-dark" id="recipientEmpty">Tidak ada pengguna yang cocok.</div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const msgBase = <?= json_encode($msgBase) ?>;

  // --- Cari percakapan (filter sisi klien) ---
  const convSearch = document.getElementById('convSearch');
  if (convSearch) {
    convSearch.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      document.querySelectorAll('#convList .conv-item').forEach(function (el) {
        el.style.display = (!q || (el.dataset.name || '').indexOf(q) !== -1) ? '' : 'none';
      });
    });
  }

  // --- Cari penerima di modal Pesan Baru ---
  const recSearch = document.getElementById('recipientSearch');
  if (recSearch) {
    recSearch.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      let shown = 0;
      document.querySelectorAll('#recipientList .recipient-item').forEach(function (el) {
        const ok = (!q || (el.dataset.name || '').indexOf(q) !== -1);
        el.style.display = ok ? '' : 'none';
        if (ok) shown++;
      });
      const empty = document.getElementById('recipientEmpty');
      if (empty) empty.classList.toggle('d-none', shown !== 0);
    });
  }

  // --- Mode Pilih & Hapus ---
  let deleteMode = false;
  const toggleBtn   = document.getElementById('toggleDeleteBtn');
  const cancelBtn   = document.getElementById('cancelDeleteBtn');
  const deleteBar   = document.getElementById('deleteBar');
  const confirmBtn  = document.getElementById('confirmDeleteBtn');
  const selCount    = document.getElementById('selCount');
  const selectAllCb = document.getElementById('selectAllCb');

  function refreshSel() {
    const cbs   = document.querySelectorAll('.conv-cb');
    const n     = document.querySelectorAll('.conv-cb:checked').length;
    if (selCount) selCount.textContent = n;
    if (confirmBtn) confirmBtn.disabled = (n === 0);
    if (selectAllCb) selectAllCb.checked = (cbs.length > 0 && n === cbs.length);
  }

  function setDeleteMode(on) {
    deleteMode = on;
    if (deleteBar) deleteBar.classList.toggle('d-none', !on);
    document.querySelectorAll('.conv-check').forEach(function (el) { el.classList.toggle('d-none', !on); });
    if (!on) {
      document.querySelectorAll('.conv-cb').forEach(function (cb) { cb.checked = false; });
      if (selectAllCb) selectAllCb.checked = false;
      refreshSel();
    }
  }

  if (toggleBtn) toggleBtn.addEventListener('click', function () { setDeleteMode(!deleteMode); });
  if (cancelBtn) cancelBtn.addEventListener('click', function () { setDeleteMode(false); });

  if (selectAllCb) {
    selectAllCb.addEventListener('change', function () {
      document.querySelectorAll('.conv-cb').forEach(function (cb) { cb.checked = selectAllCb.checked; });
      refreshSel();
    });
  }

  document.querySelectorAll('.conv-cb').forEach(function (cb) { cb.addEventListener('change', refreshSel); });

  // --- Klik baris percakapan ---
  document.querySelectorAll('#convList .conv-item').forEach(function (el) {
    el.style.cursor = 'pointer';
    el.addEventListener('click', function (e) {
      if (e.target.closest('.conv-check')) return;
      if (deleteMode) {
        const cb = el.querySelector('.conv-cb');
        if (cb) { cb.checked = !cb.checked; refreshSel(); }
        return;
      }
      window.location.href = el.dataset.href;
    });
  });

  if (confirmBtn) {
    confirmBtn.closest('form').addEventListener('submit', function (e) {
      if (document.querySelectorAll('.conv-cb:checked').length === 0) { e.preventDefault(); return; }
      if (!confirm('Hapus percakapan terpilih? Percakapan akan disembunyikan dari daftar Anda (dapat muncul kembali bila ada pesan baru).')) {
        e.preventDefault();
      }
    });
  }

  // --- Konfirmasi Hapus Semua ---
  const deleteAllForm = document.getElementById('deleteAllForm');
  if (deleteAllForm) {
    deleteAllForm.addEventListener('submit', function (e) {
      if (!confirm('Hapus SEMUA percakapan? Semua percakapan akan disembunyikan dari daftar Anda (dapat muncul kembali bila ada pesan baru).')) {
        e.preventDefault();
      }
    });
  }

  // --- Polling ringkasan (badge belum dibaca) tiap 12 detik ---
  function pollSummary() {
    fetch(msgBase + '/summary', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d || deleteMode) return;
        const byCid = {};
        (d.items || []).forEach(function (it) { byCid[it.conversation_id] = it; });
        document.querySelectorAll('#convList .conv-item').forEach(function (el) {
          const it = byCid[el.dataset.cid];
          const badge = el.querySelector('.conv-badge');
          const prev  = el.querySelector('.conv-preview');
          if (it) {
            if (badge) {
              if (it.unread > 0) { badge.textContent = it.unread; badge.classList.remove('d-none'); el.classList.add('bg-light'); }
              else { badge.classList.add('d-none'); el.classList.remove('bg-light'); }
            }
            if (prev && it.preview) prev.textContent = it.preview;
          }
        });
        const lb = document.getElementById('listUnreadBadge');
        if (lb) { if (d.total > 0) { lb.textContent = d.total + ' baru'; lb.classList.remove('d-none'); } else { lb.classList.add('d-none'); } }
      })
      .catch(function () {});
  }
  setInterval(pollSummary, 12000);
})();
</script>
<?= $this->endSection() ?>
