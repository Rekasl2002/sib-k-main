<?php
/**
 * View form Penugasan.
 * Peran/izin: Dipakai Koordinator BK untuk memberi tugas tercatat ke Guru BK.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$row = is_array($row ?? null) ? $row : [];
$options = is_array($options ?? null) ? $options : [];
$routePrefix = (string) ($routePrefix ?? '');
$action = (string) ($action ?? current_url());
$value = static fn(string $key, $default = '') => old($key, $row[$key] ?? $default);
?>
<div class="row"><div class="col-12"><div class="page-title-box d-sm-flex align-items-center justify-content-between"><h4 class="mb-sm-0"><?= esc($title ?? 'Penugasan') ?></h4><div class="d-flex align-items-center flex-wrap gap-3 page-title-right"><ol class="breadcrumb m-0"><li class="breadcrumb-item"><a href="<?= base_url('counselor/dashboard') ?>">Guru BK</a></li><li class="breadcrumb-item"><a href="<?= site_url($routePrefix) ?>">Penugasan</a></li><li class="breadcrumb-item active"><?= ! empty($row['id']) ? 'Edit' : 'Tambah' ?></li></ol><a href="<?= site_url($routePrefix) ?>" class="btn btn-outline-secondary">Kembali</a></div></div></div></div>
<form method="post" action="<?= esc($action, 'attr') ?>">
  <?= csrf_field() ?>
  <div class="card"><div class="card-body">
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Jenis Tugas</label><select name="assignment_type" class="form-select"><?php foreach (['Kelas Binaan','Tugas Layanan','Tindak Lanjut','Koordinasi'] as $type): ?><option value="<?= esc($type) ?>" <?= $value('assignment_type','Tugas Layanan') === $type ? 'selected' : '' ?>><?= esc($type) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6 mb-3"><label class="form-label">Guru BK yang Ditugaskan</label><select name="assigned_to_user_id" class="form-select"><?php foreach (($options['counselors'] ?? []) as $user): ?><option value="<?= esc((string) $user['id']) ?>" <?= (string) $value('assigned_to_user_id') === (string) $user['id'] ? 'selected' : '' ?>><?= esc($user['full_name'] ?? '-') ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="mb-3"><label class="form-label">Judul/Topik/Masalah</label><input type="text" name="title" class="form-control" required value="<?= esc($value('title')) ?>"></div>
    <div class="mb-3"><label class="form-label">Instruksi</label><textarea name="instruction" class="form-control" rows="5"><?= esc($value('instruction')) ?></textarea></div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label">Kelas</label><select name="class_id" class="form-select"><option value="">Tidak dipilih</option><?php foreach (($options['classes'] ?? []) as $class): ?><option value="<?= esc((string) $class['id']) ?>" <?= (string) $value('class_id') === (string) $class['id'] ? 'selected' : '' ?>><?= esc($class['class_name'] ?? '-') ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4 mb-3"><label class="form-label">Siswa</label><select name="student_id" class="form-select"><option value="">Tidak dipilih</option><?php foreach (($options['students'] ?? []) as $student): ?><option value="<?= esc((string) $student['id']) ?>" <?= (string) $value('student_id') === (string) $student['id'] ? 'selected' : '' ?>><?= esc(($student['full_name'] ?? '-') . ' - ' . ($student['class_name'] ?? '-')) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4 mb-3"><label class="form-label">Batas Waktu</label><input type="datetime-local" name="due_at" class="form-control" value="<?= esc(str_replace(' ', 'T', substr((string) $value('due_at'), 0, 16))) ?>"></div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Prioritas</label><select name="priority" class="form-select"><?php foreach (['Rendah','Sedang','Tinggi','Mendesak'] as $priority): ?><option value="<?= esc($priority) ?>" <?= $value('priority','Sedang') === $priority ? 'selected' : '' ?>><?= esc($priority) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6 mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['Draft','Ditugaskan','Dibaca','Berjalan','Selesai','Dibatalkan'] as $status): ?><option value="<?= esc($status) ?>" <?= $value('status','Ditugaskan') === $status ? 'selected' : '' ?>><?= esc($status) ?></option><?php endforeach; ?></select></div>
    </div>
    <button class="btn btn-primary">Simpan</button>
  </div></div>
</form>
<?= $this->endSection() ?>

