<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
/**
 * File: app/Views/{peran}/messages/chat.php
 * Fitur: Pesan Internal — HALAMAN PERCAKAPAN (gelembung chat ala WhatsApp).
 * Identik untuk semua peran; data & izin dikirim dari controller per peran.
 */
helper(['url']);

$basePath = trim((string)($basePath ?? ''), '/');
$msgBase  = site_url($basePath . '/messages');
$other    = $other ?? [];
$otherId  = (int)($other['id'] ?? 0);

// Aturan lampiran (dikirim controller; ada nilai cadangan agar aman).
$attachMax   = (int)($attachMax ?? 5);
$attachMaxMb = (int)($attachMaxMb ?? 5);
$attachExts  = array_values($attachExts ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip']);
$acceptAttr  = '.' . implode(',.', $attachExts);

$roleColor = static function (int $roleId): string {
    return [1 => '#556ee6', 2 => '#34c38f', 3 => '#50a5f1', 4 => '#f1b44c', 5 => '#6f42c1', 6 => '#e83e8c'][$roleId] ?? '#74788d';
};
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
$avatar = function (array $info, int $size) use ($photoSrc, $roleColor): string {
    $src  = $photoSrc($info);
    $name = trim((string)($info['full_name'] ?? '?'));
    if ($src !== '') {
        return '<img src="' . esc($src, 'attr') . '" class="rounded-circle" style="width:' . $size . 'px;height:' . $size . 'px;object-fit:cover;" alt="' . esc($name, 'attr') . '">';
    }
    $parts = preg_split('/\s+/', $name);
    $ini   = strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    $bg    = $roleColor((int)($info['role_id'] ?? 0));
    return '<span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-semibold" style="width:' . $size . 'px;height:' . $size . 'px;background:' . $bg . ';font-size:' . (int)($size * 0.4) . 'px;">' . esc($ini) . '</span>';
};
?>

<style>
  .chat-wrap { display:flex; flex-direction:column; height:calc(100vh - 230px); min-height:420px; }
  .chat-body { flex:1 1 auto; overflow-y:auto; padding:1rem; background:#f5f6fa; }
  .chat-row { display:flex; margin-bottom:.5rem; }
  .chat-row.mine { justify-content:flex-end; }
  .chat-bubble { max-width:75%; padding:.5rem .75rem; border-radius:.75rem; background:#fff; box-shadow:0 1px 1px rgba(0,0,0,.08); color:#212529; }
  .chat-row.mine .chat-bubble { background:#d9fdd3; }
  .chat-bubble .chat-text { white-space:pre-wrap; word-break:break-word; }
  .chat-bubble .chat-time { font-size:.7rem; color:#555; text-align:right; margin-top:.15rem; }
  .chat-bubble .chat-att a { font-size:.8rem; }
</style>

<div class="row">
  <div class="col-12">
    <div class="card mb-0">
      <!-- Header percakapan -->
      <div class="card-header d-flex align-items-center gap-2">
        <a href="<?= $msgBase ?>" class="btn btn-light btn-sm" title="Kembali"><i class="mdi mdi-arrow-left"></i></a>
        <div class="flex-shrink-0"><?= $avatar($other, 42) ?></div>
        <div class="flex-grow-1" style="min-width:0;">
          <h5 class="mb-0 text-dark text-truncate"><?= esc($other['full_name'] ?? 'Pengguna') ?></h5>
          <small class="text-dark text-truncate d-block"><?= esc($other['subtitle'] ?? '') ?></small>
        </div>
      </div>

      <div class="chat-wrap">
        <!-- Daftar gelembung -->
        <div class="chat-body" id="chatBody">
          <?php if (empty($messages)): ?>
            <div class="text-center text-dark py-5" id="chatEmpty">
              <i class="mdi mdi-message-text-outline" style="font-size:42px;"></i>
              <p class="mt-2 mb-0">Belum ada pesan. Tulis pesan pertama Anda untuk memulai percakapan.</p>
            </div>
          <?php else: ?>
            <?php foreach ($messages as $m): ?>
              <?php $mine = ((int)$m['created_by'] === (int)session('user_id')); ?>
              <div class="chat-row <?= $mine ? 'mine' : '' ?>">
                <div class="chat-bubble">
                  <?php if (trim((string)$m['body']) !== ''): ?>
                    <div class="chat-text"><?= esc($m['body']) ?></div>
                  <?php endif; ?>
                  <?php foreach (($m['attachments'] ?? []) as $a): ?>
                    <div class="chat-att"><i class="mdi mdi-paperclip"></i>
                      <a href="<?= esc($a['url'], 'attr') ?>"><?= esc($a['name']) ?></a>
                    </div>
                  <?php endforeach; ?>
                  <div class="chat-time"><?= esc(date('H:i', strtotime((string)$m['created_at']))) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Composer -->
        <div class="card-footer">
          <?php // Jaring pengaman: tampilkan pesan error bila pengiriman jatuh ke mode non-AJAX (mis. JS gagal). ?>
          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-1 px-2 mb-2 small" role="alert">
              <i class="mdi mdi-alert-circle me-1"></i><?= esc(session()->getFlashdata('error')) ?>
            </div>
          <?php endif; ?>
          <div id="chatError" class="alert alert-danger py-1 px-2 mb-2 d-none small" role="alert"></div>
          <form id="chatForm" method="post" action="<?= site_url($basePath . '/messages/send/' . $otherId) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="d-flex align-items-end gap-2">
              <label class="btn btn-light mb-0" title="Lampirkan berkas">
                <i class="mdi mdi-paperclip"></i>
                <input type="file" name="attachments[]" id="chatFiles" multiple accept="<?= esc($acceptAttr, 'attr') ?>" class="d-none">
              </label>
              <textarea name="body" id="chatInput" class="form-control" rows="1" placeholder="Tulis pesan..." style="resize:none;max-height:120px;"></textarea>
              <button type="submit" class="btn btn-primary" id="chatSend"><i class="mdi mdi-send"></i></button>
            </div>
            <div id="fileHint" class="small text-dark mt-1 d-none"></div>
            <div class="small text-muted mt-1">
              <i class="mdi mdi-information-outline me-1"></i>
              Lampiran maksimal <?= $attachMax ?> berkas, masing-masing &le; <?= $attachMaxMb ?> MB.
              Jenis yang diperbolehkan: <?= esc(implode(', ', $attachExts)) ?>.
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const msgBase  = <?= json_encode($msgBase) ?>;
  const otherId  = <?= (int)$otherId ?>;
  const csrfName = '<?= csrf_token() ?>';
  let   csrfHash = '<?= csrf_hash() ?>';
  let   lastId   = <?= (int)($lastMessageId ?? 0) ?>;

  // Aturan lampiran (sinkron dengan controller).
  const maxFiles   = <?= (int)$attachMax ?>;
  const maxMb      = <?= (int)$attachMaxMb ?>;
  const allowedExt = <?= json_encode($attachExts) ?>;

  const body  = document.getElementById('chatBody');
  const form  = document.getElementById('chatForm');
  const input = document.getElementById('chatInput');
  const files = document.getElementById('chatFiles');
  const hint  = document.getElementById('fileHint');
  const errBox = document.getElementById('chatError');
  const empty = document.getElementById('chatEmpty');

  function showError(msg) {
    if (!errBox) return;
    errBox.textContent = msg;
    errBox.classList.remove('d-none');
  }
  function clearError() {
    if (errBox) errBox.classList.add('d-none');
  }

  // Validasi lampiran di sisi klien (peringatan instan sebelum dikirim ke server).
  function validateFiles() {
    if (!files || !files.files || files.files.length === 0) return null;
    const list = files.files;
    if (list.length > maxFiles) {
      return 'Lampiran maksimal ' + maxFiles + ' berkas sekaligus. Anda memilih ' + list.length + ' berkas.';
    }
    for (let i = 0; i < list.length; i++) {
      const f = list[i];
      const dot = f.name.lastIndexOf('.');
      const ext = dot >= 0 ? f.name.slice(dot + 1).toLowerCase() : '';
      if (allowedExt.indexOf(ext) === -1) {
        return 'Jenis berkas "' + (ext || f.name) + '" tidak diizinkan. Jenis yang diperbolehkan: ' + allowedExt.join(', ') + '.';
      }
      if (f.size > maxMb * 1024 * 1024) {
        return 'Berkas "' + f.name + '" melebihi batas ' + maxMb + ' MB.';
      }
    }
    return null;
  }

  function scrollBottom() { if (body) body.scrollTop = body.scrollHeight; }
  scrollBottom();

  // Textarea tumbuh otomatis
  if (input) {
    input.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    // Enter kirim, Shift+Enter baris baru
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });
  }

  if (files) {
    files.addEventListener('change', function () {
      // Peringatan langsung saat berkas dipilih.
      const err = validateFiles();
      if (err) { showError(err); } else { clearError(); }
      if (!hint) return;
      if (this.files.length) { hint.textContent = this.files.length + ' berkas dipilih'; hint.classList.remove('d-none'); }
      else { hint.classList.add('d-none'); }
    });
  }

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function appendBubble(m) {
    if (empty) empty.remove();
    const row = document.createElement('div');
    row.className = 'chat-row' + (m.mine ? ' mine' : '');
    let att = '';
    (m.attachments || []).forEach(function (a) {
      att += '<div class="chat-att"><i class="mdi mdi-paperclip"></i> <a href="' + a.url + '">' + escHtml(a.name) + '</a></div>';
    });
    const text = (m.body && m.body.trim() !== '') ? '<div class="chat-text">' + escHtml(m.body) + '</div>' : '';
    row.innerHTML = '<div class="chat-bubble">' + text + att + '<div class="chat-time">' + escHtml(m.time || '') + '</div></div>';
    body.appendChild(row);
    if (m.id && m.id > lastId) lastId = m.id;
    scrollBottom();
  }

  // Ambil token CSRF terbaru dari server (GET → tidak meregenerasi token).
  // Dipakai untuk pulih dari token basi: token dapat diregenerasi oleh proses
  // lain (mis. menandai notifikasi terbaca dari lonceng saat berpindah ke
  // halaman ini), sehingga token yang tertanam di form menjadi usang.
  function refreshCsrf() {
    return fetch(<?= json_encode(site_url('api/csrf')) ?>, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) { if (j && j.token) csrfHash = j.token; })
      .catch(function () {});
  }

  // Kirim sekali; bila ditolak CSRF (403) ambil token baru lalu coba ulang sekali.
  function postMessage(fd, retried) {
    fd.set(csrfName, csrfHash);
    return fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
      .then(function (r) {
        if (r.status === 403 && !retried) {
          return refreshCsrf().then(function () { return postMessage(fd, true); });
        }
        return r.json().then(function (d) { return { ok: r.ok, d: d }; }, function () { return { ok: false, d: null }; });
      });
  }

  // Kirim via AJAX
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const text = (input.value || '').trim();
      if (text === '' && (!files || files.files.length === 0)) {
        showError('Tulis pesan atau lampirkan berkas terlebih dahulu.');
        return;
      }
      // Validasi lampiran sebelum kirim — tampilkan peringatan & batalkan bila tak lolos.
      const fErr = validateFiles();
      if (fErr) { showError(fErr); return; }
      clearError();

      const fd = new FormData(form);
      document.getElementById('chatSend').disabled = true;
      postMessage(fd, false)
        .then(function (res) {
          if (res.d && res.d.csrf) csrfHash = res.d.csrf;
          if (res.ok && res.d && res.d.status === 'ok') {
            appendBubble(res.d.message);
            input.value = ''; input.style.height = 'auto';
            if (files) files.value = '';
            if (hint) hint.classList.add('d-none');
            clearError();
          } else {
            showError((res.d && res.d.message) || 'Gagal mengirim pesan.');
          }
        })
        .catch(function () { showError('Gagal mengirim pesan.'); })
        .finally(function () { document.getElementById('chatSend').disabled = false; });
    });
  }

  // Polling pesan baru tiap 5 detik
  function poll() {
    fetch(msgBase + '/poll/' + otherId + '?after=' + lastId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d || !d.messages) return;
        d.messages.forEach(function (m) { if (!m.mine) appendBubble(m); });
        if (d.last_id && d.last_id > lastId) lastId = d.last_id;
      })
      .catch(function () {});
  }
  setInterval(poll, 5000);
})();
</script>
<?= $this->endSection() ?>
