<?php
/**
 * File Path: app/Views/role_features/trash/_table.php
 *
 * Partial bersama tampilan Tempat Sampah (dipakai semua peran).
 *
 * @var list<array<string,mixed>> $items
 * @var string $basePath
 */
$basePath = $basePath ?? 'dashboard';
$items    = $items ?? [];
?>
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <h4 class="mb-1 text-dark">Tempat Sampah</h4>
    <div class="text-dark">
      Data yang Anda hapus tersimpan sementara di sini. Anda dapat memulihkannya kembali
      atau menghapusnya permanen. Hanya Anda yang dapat melihat data ini.
    </div>
  </div>
  <ol class="breadcrumb m-0">
    <li class="breadcrumb-item"><a href="<?= base_url($basePath . '/dashboard') ?>">Beranda</a></li>
    <li class="breadcrumb-item active">Tempat Sampah</li>
  </ol>
</div>

<?php if (session('success')): ?>
  <div class="alert alert-success"><i class="mdi mdi-check-circle me-1"></i><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
  <div class="alert alert-danger"><i class="mdi mdi-alert-circle me-1"></i><?= esc(session('error')) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <?php if (empty($items)): ?>
      <div class="text-center text-dark py-5">
        <i class="mdi mdi-trash-can-outline d-block mb-2" style="font-size:48px;"></i>
        <div class="fw-semibold">Tempat sampah Anda kosong.</div>
        <div>Data yang Anda hapus akan muncul di sini dan bisa dipulihkan.</div>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr class="text-dark">
              <th style="width:1%">No</th>
              <th>Jenis Data</th>
              <th>Judul / Keterangan</th>
              <th>Dihapus pada</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $i => $it): ?>
              <tr class="text-dark">
                <td><?= $i + 1 ?></td>
                <td><span class="badge bg-secondary"><?= esc($it['label']) ?></span></td>
                <td><?= esc($it['title']) ?></td>
                <td><?= esc(function_exists('indonesian_datetime') ? indonesian_datetime($it['deleted_at']) : (string) $it['deleted_at']) ?></td>
                <td class="text-end text-nowrap">
                  <form method="post" action="<?= base_url($basePath . '/trash/restore') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="entity" value="<?= esc($it['entity'], 'attr') ?>">
                    <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success" title="Pulihkan data" data-bs-toggle="tooltip">
                      <i class="mdi mdi-restore"></i>
                    </button>
                  </form>
                  <form method="post" action="<?= base_url($basePath . '/trash/force-delete') ?>" class="d-inline"
                        onsubmit="return confirm('Hapus permanen data ini? Tindakan ini tidak dapat dibatalkan.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="entity" value="<?= esc($it['entity'], 'attr') ?>">
                    <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus permanen" data-bs-toggle="tooltip">
                      <i class="mdi mdi-delete-forever"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
