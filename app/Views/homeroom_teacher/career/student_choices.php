<!-- app/Views/homeroom_teacher/career/student_choices.php -->
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
      <div>
        <h4 class="mb-1">Pilihan Karir & Perguruan Tinggi Siswa</h4>
        <p class="text-muted mb-0">
          Rekap pilihan karir dan perguruan tinggi siswa di kelas perwalian Anda.
        </p>
      </div>

      <div class="d-flex gap-2">
        <a href="<?= route_to('homeroom.career.index') ?>" class="btn btn-light btn-sm">
          &laquo; Kembali ke Info Karir &amp; PT
        </a>
      </div>
    </div>

    <?php
    $activeTab = $activeTab ?? 'careers';
    $filters   = $filters   ?? ['q' => '', 'class_id' => '', 'sort' => '', 'per_page' => 10];
    ?>

    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'careers' ? 'active' : '' ?>"
           href="<?= site_url('homeroom/career-info/student-choices?tab=careers'
             . '&q=' . urlencode((string)($filters['q'] ?? ''))
             . '&sort=' . urlencode((string)($filters['sort'] ?? ''))
             . '&per_page=' . urlencode((string)($filters['per_page'] ?? 10))
           ) ?>">
          Karir
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'universities' ? 'active' : '' ?>"
           href="<?= site_url('homeroom/career-info/student-choices?tab=universities'
             . '&q=' . urlencode((string)($filters['q'] ?? ''))
             . '&sort=' . urlencode((string)($filters['sort'] ?? ''))
             . '&per_page=' . urlencode((string)($filters['per_page'] ?? 10))
           ) ?>">
          Perguruan Tinggi
        </a>
      </li>
    </ul>

    <!-- Filter bar -->
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-2" method="get" action="<?= site_url('homeroom/career-info/student-choices') ?>">
          <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">

          <div class="col-md-4">
            <label class="form-label">Cari Siswa / Kelas / Pilihan</label>
            <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>"
                   class="form-control form-control-sm"
                   placeholder="Nama siswa / NIS / kelas / judul karir / universitas">
          </div>

          <div class="col-md-3">
            <label class="form-label">Kelas Perwalian</label>
            <select name="class_id" class="form-select form-select-sm" disabled>
              <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $cls): ?>
                  <option value="<?= (int) $cls['id'] ?>" selected>
                    <?= esc(($cls['grade_level'] ?? '') . ' - ' . ($cls['class_name'] ?? '')) ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="">(Kelas perwalian tidak ditemukan)</option>
              <?php endif; ?>
            </select>
            <input type="hidden" name="class_id" value="<?= esc($filters['class_id'] ?? '') ?>">
          </div>

          <div class="col-md-2">
            <label class="form-label">Urutkan</label>
            <select name="sort" class="form-select form-select-sm">
              <option value="">Nama siswa (A-Z)</option>
              <option value="student_desc" <?= ($filters['sort'] ?? '') === 'student_desc' ? 'selected' : '' ?>>
                Nama siswa (Z-A)
              </option>
              <option value="class" <?= ($filters['sort'] ?? '') === 'class' ? 'selected' : '' ?>>
                Kelas
              </option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Per halaman</label>
            <select name="per_page" class="form-select form-select-sm">
              <?php foreach ([10, 25, 50, 100] as $pp): ?>
                <option value="<?= $pp ?>" <?= (int)($filters['per_page'] ?? 10) === $pp ? 'selected' : '' ?>>
                  <?= $pp ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm w-100">Terapkan</button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($activeTab === 'careers'): ?>

      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">Pilihan Karir Siswa</h5>

          <div class="table-responsive">
            <table class="table table-sm align-middle table-bordered">
              <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Siswa</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Judul Karir</th>
                <th>Sektor</th>
                <th>Min. Pendidikan</th>
                <th>Tanggal Simpan</th>
              </tr>
              </thead>
              <tbody>
              <?php if (!empty($careerChoices)): ?>
                <?php
                $page   = $careerPager?->getCurrentPage('student_careers') ?? 1;
                $per    = $careerPager?->getPerPage('student_careers') ?? (int)($filters['per_page'] ?? 10);
                $no     = 1 + ($page - 1) * $per;
                ?>
                <?php foreach ($careerChoices as $row): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= esc($row['student_name'] ?? '-') ?></td>
                    <td><?= esc($row['nis'] ?? '-') ?></td>
                    <td><?= esc(($row['grade_level'] ?? '') . ' - ' . ($row['class_name'] ?? '')) ?></td>
                    <td><?= esc($row['career_title'] ?? '-') ?></td>
                    <td><?= esc($row['sector'] ?? '-') ?></td>
                    <td><?= esc($row['min_education'] ?? '-') ?></td>
                    <td><?= esc($row['saved_at'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center text-muted">
                    Belum ada pilihan karir yang disimpan oleh siswa di kelas ini.
                  </td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if (!empty($careerPager)): ?>
            <div class="mt-2">
              <?= $careerPager->links('student_careers', 'default_full') ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    <?php else: ?>

      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">Pilihan Perguruan Tinggi Siswa</h5>

          <div class="table-responsive">
            <table class="table table-sm align-middle table-bordered">
              <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Siswa</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Universitas</th>
                <th>Akreditasi</th>
                <th>Lokasi</th>
                <th>Tanggal Simpan</th>
              </tr>
              </thead>
              <tbody>
              <?php if (!empty($universityChoices)): ?>
                <?php
                $page   = $universityPager?->getCurrentPage('student_universities') ?? 1;
                $per    = $universityPager?->getPerPage('student_universities') ?? (int)($filters['per_page'] ?? 10);
                $no     = 1 + ($page - 1) * $per;
                ?>
                <?php foreach ($universityChoices as $row): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= esc($row['student_name'] ?? '-') ?></td>
                    <td><?= esc($row['nis'] ?? '-') ?></td>
                    <td><?= esc(($row['grade_level'] ?? '') . ' - ' . ($row['class_name'] ?? '')) ?></td>
                    <td><?= esc($row['university_name'] ?? '-') ?></td>
                    <td><?= esc($row['accreditation'] ?? '-') ?></td>
                    <td><?= esc($row['location'] ?? '-') ?></td>
                    <td><?= esc($row['saved_at'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center text-muted">
                    Belum ada pilihan perguruan tinggi yang disimpan oleh siswa di kelas ini.
                  </td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if (!empty($universityPager)): ?>
            <div class="mt-2">
              <?= $universityPager->links('student_universities', 'default_full') ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<?= $this->endSection() ?>
