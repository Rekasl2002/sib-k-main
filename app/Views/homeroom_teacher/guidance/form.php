<?php
/**
 * View form per peran fitur layanan BK.
 * Fitur: Bimbingan/Konseling/Kolaborasi Orang Tua/Kunjungan Rumah/Konferensi
 * Kasus. Field khusus ditampilkan berdasarkan $serviceType.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$detail = is_array($row['detail'] ?? null) ? $row['detail'] : [];
$options = is_array($options ?? null) ? $options : [];
$action = (string) ($action ?? current_url());
$routePrefix = (string) ($routePrefix ?? '');
$serviceType = (string) ($serviceType ?? '');
$value = static function (string $key, $default = '') use ($row, $detail) {
    return old($key, $row[$key] ?? $detail[$key] ?? $default);
};
?>

<div class="row">
  <div class="col-12">
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
      <h4 class="mb-sm-0"><?= esc($title ?? 'Form Layanan BK') ?></h4>
      <div class="d-flex align-items-center flex-wrap gap-3 page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= base_url('homeroom/dashboard') ?>">Wali Kelas</a></li>
          <li class="breadcrumb-item"><a href="<?= site_url($routePrefix) ?>"><?= esc($serviceType) ?></a></li>
          <li class="breadcrumb-item active"><?= ! empty($row['id']) ? 'Edit' : 'Tambah' ?></li>
        </ol>
        <a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a>
      </div>
    </div>
  </div>
</div>

<form method="post" action="<?= esc($action, 'attr') ?>">
  <?= csrf_field() ?>
  <?php if (! empty($row['id'])): ?>
    <input type="hidden" name="replace_participants" value="1">
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Data Utama</h5>
          <div class="mb-3">
            <label class="form-label">Topik/Judul</label>
            <input type="text" name="title" class="form-control" required value="<?= esc($value('title')) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Siswa</label>
              <select name="target_student_id" class="form-select">
                <option value="">Tidak dipilih</option>
                <?php foreach (($options['students'] ?? []) as $student): ?>
                  <option value="<?= esc((string) $student['id']) ?>" <?= (string) $value('target_student_id') === (string) $student['id'] ? 'selected' : '' ?>>
                    <?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Kelas</label>
              <select name="target_class_id" class="form-select">
                <option value="">Tidak dipilih</option>
                <?php foreach (($options['classes'] ?? []) as $class): ?>
                  <option value="<?= esc((string) $class['id']) ?>" <?= (string) $value('target_class_id') === (string) $class['id'] ? 'selected' : '' ?>>
                    <?= esc($class['class_name'] ?? '-') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Guru BK/Penanggung Jawab</label>
              <select name="counselor_id" class="form-select">
                <?php foreach (($options['counselors'] ?? []) as $user): ?>
                  <option value="<?= esc((string) $user['id']) ?>" <?= (string) $value('counselor_id', session('user_id')) === (string) $user['id'] ? 'selected' : '' ?>>
                    <?= esc($user['full_name'] ?? '-') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Draft','Dijadwalkan','Berlangsung','Selesai','Dibatalkan','Perlu Tindak Lanjut'] as $status): ?>
                  <option value="<?= esc($status) ?>" <?= $value('status', 'Dijadwalkan') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Tanggal/Jadwal</label>
              <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= esc(str_replace(' ', 'T', substr((string) $value('scheduled_at'), 0, 16))) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Durasi (menit)</label>
              <input type="number" name="duration_minutes" class="form-control" value="<?= esc($value('duration_minutes', 60)) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Tempat/Lokasi</label>
              <input type="text" name="location" class="form-control" value="<?= esc($value('location')) ?>">
            </div>
          </div>

          <?php if ($serviceType === 'Bimbingan'): ?>
            <div class="mb-3">
              <label class="form-label">Jenis Bimbingan</label>
              <select name="guidance_type" class="form-select">
                <?php foreach (['Kelompok','Klasikal','Kelas Besar'] as $type): ?>
                  <option value="<?= esc($type) ?>" <?= $value('guidance_type', 'Klasikal') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Ringkasan Materi</label>
              <textarea name="summary" class="form-control" rows="4"><?= esc($value('summary')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Konseling'): ?>
            <div class="mb-3">
              <label class="form-label">Jenis Konseling</label>
              <select name="counseling_type" class="form-select">
                <?php foreach (['Individu','Kelompok'] as $type): ?>
                  <option value="<?= esc($type) ?>" <?= $value('counseling_type', 'Individu') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Deskripsi Masalah</label>
              <textarea name="problem_description" class="form-control" rows="4"><?= esc($value('problem_description')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Rencana Tindak Lanjut</label>
              <textarea name="follow_up_plan" class="form-control" rows="3"><?= esc($value('follow_up_plan')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Kolaborasi Orang Tua'): ?>
            <div class="mb-3">
              <label class="form-label">Nama Orang Tua/Pihak yang Hadir</label>
              <textarea name="parent_names" class="form-control" rows="3"><?= esc($value('parent_name')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Ringkasan dan Tindak Lanjut</label>
              <textarea name="summary" class="form-control" rows="4"><?= esc($value('summary')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Kunjungan Rumah'): ?>
            <div class="mb-3">
              <label class="form-label">Alamat Rumah</label>
              <textarea name="address" class="form-control" rows="3"><?= esc($value('address_snapshot')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Judul/Masalah</label>
              <input type="text" name="problem_topic" class="form-control" value="<?= esc($value('problem_topic')) ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Hasil Kunjungan</label>
              <textarea name="visit_result" class="form-control" rows="4"><?= esc($value('visit_result')) ?></textarea>
            </div>
          <?php elseif ($serviceType === 'Konferensi Kasus'): ?>
            <div class="mb-3">
              <label class="form-label">Kronologi/Ringkasan Masalah</label>
              <textarea name="chronology" class="form-control" rows="3"><?= esc($value('chronology')) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Pembahasan dan Keputusan</label>
              <textarea name="decision_summary" class="form-control" rows="4"><?= esc($value('decision_summary')) ?></textarea>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Peserta dan Catatan</h5>
          <div class="mb-3">
            <label class="form-label">Pihak yang Menghadiri/Diundang</label>
            <textarea name="manual_participants" class="form-control" rows="5" placeholder="Contoh:&#10;Nama Orang Tua - Orang Tua&#10;Nama Wali Kelas - Wali Kelas"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Catatan Awal</label>
            <textarea name="initial_note" class="form-control" rows="5"><?= esc($value('initial_note')) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Kerahasiaan</label>
            <select name="privacy_level" class="form-select">
              <?php foreach (['Umum Terbatas','Rahasia BK','Rahasia Tinggi'] as $privacy): ?>
                <option value="<?= esc($privacy) ?>" <?= $value('privacy_level', 'Rahasia BK') === $privacy ? 'selected' : '' ?>><?= esc($privacy) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary w-100" type="submit">Simpan</button>
        </div>
      </div>
    </div>
  </div>
</form>
<?= $this->endSection() ?>

