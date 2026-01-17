<?php
// Notifications partial

helper('settings');

// 1) Hormati toggle dari /admin/settings → Notifications
if (! setting('enable_internal', true, 'notifications')) {
    // Kalau dinonaktifkan, jangan render apa pun
    return;
}

// 2) Pastikan user login
$uid = (int) session('user_id');
if ($uid <= 0) {
    return;
}

// 3) Ambil data notifikasi
$model  = new \App\Models\NotificationModel();
$unread = $model->where('user_id', $uid)->where('is_read', 0)->countAllResults();
$items  = $model->where('user_id', $uid)->orderBy('created_at', 'DESC')->findAll(10);

// 4) Siapkan token CSRF untuk AJAX (sesuai .env kamu: headerName = X-CSRF-TOKEN)
$csrfHeader = config('Security')->headerName; // biasanya 'X-CSRF-TOKEN'
$csrfHash   = csrf_hash();
?>

<div class="dropdown d-inline-block">
  <button type="button" class="btn header-item noti-icon" id="page-header-notifications-dropdown"
          data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <i class="mdi mdi-bell-outline"></i>
    <?php if ($unread > 0): ?>
      <span class="badge bg-danger rounded-pill"><?= esc($unread) ?></span>
    <?php endif; ?>
  </button>

  <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
       aria-labelledby="page-header-notifications-dropdown" style="min-width:380px">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="m-0">Notifikasi</h6>
      <button class="btn btn-sm btn-link" id="notifMarkAll" type="button">Tandai dibaca</button>
    </div>

    <div style="max-height:420px; overflow:auto">
      <?php if (! $items): ?>
        <div class="p-3 text-center text-muted">Tidak ada notifikasi.</div>
      <?php else: foreach ($items as $n): ?>
        <a href="<?= esc($n['link'] ?? '#') ?>"
           class="text-reset notification-item d-block <?= $n['is_read'] ? '' : 'bg-light' ?>"
           data-notif-id="<?= (int) $n['id'] ?>">
          <div class="d-flex">
            <div class="avatar-xs me-3">
              <span class="avatar-title bg-primary bg-soft rounded-circle font-size-16">
                <i class="mdi mdi-bell"></i>
              </span>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-1"><?= esc($n['title']) ?></h6>
              <?php if (! empty($n['message'])): ?>
                <div class="text-muted text-truncate"><?= esc($n['message']) ?></div>
              <?php endif; ?>
              <p class="mb-0"><small class="text-muted"><?= esc($n['created_at']) ?></small></p>
            </div>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  const CSRF_HEADER = <?= json_encode($csrfHeader) ?>;
  const CSRF_TOKEN  = <?= json_encode($csrfHash) ?>;

  // Tandai satu notifikasi sebagai dibaca saat diklik
  document.addEventListener('click', function(e){
    const a = e.target.closest('a.notification-item[data-notif-id]');
    if (!a) return;

    const id  = a.getAttribute('data-notif-id');
    const url = <?= json_encode(site_url('notifications/mark-read')) ?> + '/' + id;

    const headers = {'X-Requested-With':'XMLHttpRequest'};
    headers[CSRF_HEADER] = CSRF_TOKEN;

    fetch(url, {method:'POST', headers, credentials:'same-origin'})
      .then(()=> { a.classList.remove('bg-light'); }) // efek langsung
      .catch(()=> {/* diamkan saja; token bisa berubah, akan normal setelah reload */});
  });

  // Tandai semua sebagai dibaca
  document.getElementById('notifMarkAll')?.addEventListener('click', function(){
    const url = <?= json_encode(site_url('notifications/mark-all-read')) ?>;

    const headers = {'X-Requested-With':'XMLHttpRequest'};
    headers[CSRF_HEADER] = CSRF_TOKEN;

    fetch(url, {method:'POST', headers, credentials:'same-origin'})
      .then(()=> location.reload())
      .catch(()=> location.reload());
  });
})();
</script>
