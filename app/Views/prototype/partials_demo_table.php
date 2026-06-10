<?php
$records = is_array($records ?? null) ? $records : [];
$headers = $records ? array_keys($records[0]) : [];
$featureKey = (string) ($featureKey ?? '');
$roleMode = (string) ($roleMode ?? '');
$showActions = (bool) ($showActions ?? true);
$screens = is_array($screens ?? null) ? $screens : [];
$detailScreen = isset($screens['detail']) ? 'detail' : (isset($screens['thread']) ? 'thread' : '');
$editScreen = isset($screens['edit']) ? 'edit' : '';
// Hak hapus dihitung per fitur x peran di controller (sesuai Matriks CRUD) dan
// diteruskan lewat $canDelete. Jika tidak diberikan, pakai aturan lama (staf BK).
$canDelete = isset($canDelete) ? (bool) $canDelete : in_array($roleMode, ['admin', 'koordinator-bk', 'guru-bk'], true);

if (! function_exists('prototype_table_tone')) {
    function prototype_table_tone(string $status): string
    {
        return match (strtolower(trim($status))) {
            'diajukan', 'draft', 'draf', 'belum dibaca', 'belum hadir', 'menunggu konfirmasi', 'belum selesai', 'perlu diperiksa', 'perlu tindak lanjut' => 'warning',
            'dijadwalkan', 'terjadwal', 'ditinjau', 'berjalan', 'berlangsung', 'aktif', 'terkirim', 'dipublikasi' => 'info',
            'selesai', 'siap', 'siap diimpor', 'publik', 'dibaca', 'hadir', 'konfirmasi', 'tersimpan', 'diterima', 'ditugaskan', 'berhasil' => 'success',
            'ditolak', 'mendesak', 'tinggi', 'gagal', 'dibatalkan' => 'danger',
            'rahasia bk', 'rahasia tinggi', 'terbatas' => 'dark',
            default => 'secondary',
        };
    }
}
?>

<?php if ($records && $headers): ?>
  <div class="table-responsive">
    <table class="table table-hover table-bordered align-middle nowrap w-100 mb-0">
      <thead class="table-light">
        <tr>
          <th width="5%">No</th>
          <?php foreach ($headers as $header): ?>
            <th><?= esc($header) ?></th>
          <?php endforeach; ?>
          <?php if ($showActions): ?>
            <th width="12%">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $index => $row): ?>
          <tr>
            <td class="text-center"><?= (int) $index + 1 ?></td>
            <?php foreach ($headers as $header): ?>
              <?php $value = (string) ($row[$header] ?? '-'); ?>
              <td>
                <?php if (in_array(strtolower((string) $header), ['status', 'prioritas', 'akses'], true)): ?>
                  <span class="badge bg-<?= esc(prototype_table_tone($value)) ?>"><?= esc($value) ?></span>
                <?php else: ?>
                  <?= esc($value) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <?php if ($showActions): ?>
              <td class="text-center">
                <div class="btn-group" role="group">
                  <?php if ($detailScreen !== ''): ?>
                    <a href="<?= base_url('prototype/demo/' . $featureKey . '/' . $detailScreen . '/' . ((int) $index + 1) . '?role=' . rawurlencode($roleMode)) ?>" class="btn btn-sm btn-info" title="Detail">
                      <i class="mdi mdi-eye"></i>
                    </a>
                  <?php endif; ?>
                  <?php if ($editScreen !== ''): ?>
                    <a href="<?= base_url('prototype/demo/' . $featureKey . '/' . $editScreen . '/' . ((int) $index + 1) . '?role=' . rawurlencode($roleMode)) ?>" class="btn btn-sm btn-warning" title="Edit">
                      <i class="mdi mdi-pencil"></i>
                    </a>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                    <button type="button" class="btn btn-sm btn-danger" title="Hapus">
                      <i class="mdi mdi-delete"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="text-center text-muted py-5">Belum ada data.</div>
<?php endif; ?>
