<!-- app/Views/counselor/career/student_choices.php -->
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-content">
  <div class="container-fluid">

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
      <h4 class="mb-0">Pilihan Karir & Perguruan Tinggi Siswa</h4>

      <div class="d-flex gap-2">
        <a href="<?= route_to('counselor.career.index') ?>" class="btn btn-light btn-sm">
          &laquo; Kembali ke Info Karir & PT
        </a>
      </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success">
        <?= esc(session()->getFlashdata('success')) ?>
      </div>
    <?php elseif (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger">
        <?= esc(session()->getFlashdata('error')) ?>
      </div>
    <?php endif; ?>

    <!-- Sub-tab Karir vs Perguruan Tinggi -->
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'careers' ? 'active' : '' ?>"
           href="<?= site_url('counselor/career-info/student-choices?tab=careers'
             . '&q=' . urlencode((string)($filters['q'] ?? ''))
             . '&class_id=' . urlencode((string)($filters['class_id'] ?? ''))
             . '&sort=' . urlencode((string)($filters['sort'] ?? ''))
             . '&per_page=' . urlencode((string)($filters['per_page'] ?? 10))
           ) ?>">
          Karir
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'universities' ? 'active' : '' ?>"
           href="<?= site_url('counselor/career-info/student-choices?tab=universities'
             . '&q=' . urlencode((string)($filters['q'] ?? ''))
             . '&class_id=' . urlencode((string)($filters['class_id'] ?? ''))
             . '&sort=' . urlencode((string)($filters['sort'] ?? ''))
             . '&per_page=' . urlencode((string)($filters['per_page'] ?? 10))
           ) ?>">
          Perguruan Tinggi
        </a>
      </li>
    </ul>

    <div class="card">
      <div class="card-body">

        <!-- Filter & Sort -->
        <form method="get" action="<?= site_url('counselor/career-info/student-choices') ?>" class="row g-2 mb-3">
          <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">

          <div class="col-md-4 col-sm-6">
            <label class="form-label">
              Cari Siswa / NIS / <?= $activeTab === 'careers' ? 'Karir' : 'Perguruan Tinggi' ?>
            </label>
            <input type="text"
                   name="q"
                   value="<?= esc($filters['q'] ?? '') ?>"
                   class="form-control"
                   placeholder="Ketik kata kunci...">
          </div>

          <div class="col-md-3 col-sm-6">
            <label class="form-label">Kelas</label>
            <select name="class_id" class="form-select">
              <option value="">Semua kelas</option>
              <?php foreach ($classes as $cls): ?>
                <option value="<?= (int) $cls['id'] ?>"
                  <?= (string)($filters['class_id'] ?? '') === (string)$cls['id'] ? 'selected' : '' ?>>
                  <?= esc($cls['class_name'] ?? ('Kelas ' . ($cls['grade_level'] ?? ''))) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label">Urutkan</label>
            <select name="sort" class="form-select">
              <option value="">Nama siswa (A–Z)</option>
              <option value="student_desc" <?= ($filters['sort'] ?? '') === 'student_desc' ? 'selected' : '' ?>>
                Nama siswa (Z–A)
              </option>
              <option value="class" <?= ($filters['sort'] ?? '') === 'class' ? 'selected' : '' ?>>
                Kelas
              </option>

              <?php if ($activeTab === 'careers'): ?>
                <option value="career" <?= ($filters['sort'] ?? '') === 'career' ? 'selected' : '' ?>>
                  Nama karir
                </option>
              <?php else: ?>
                <option value="name" <?= ($filters['sort'] ?? '') === 'name' ? 'selected' : '' ?>>
                  Nama perguruan tinggi
                </option>
              <?php endif; ?>

              <option value="latest" <?= ($filters['sort'] ?? '') === 'latest' ? 'selected' : '' ?>>
                Terbaru disimpan
              </option>
            </select>
          </div>

          <div class="col-md-2 col-sm-6">
            <label class="form-label">Per halaman</label>
            <select name="per_page" class="form-select">
              <?php foreach ([10, 25, 50, 100] as $pp): ?>
                <option value="<?= $pp ?>" <?= (int)($filters['per_page'] ?? 10) === $pp ? 'selected' : '' ?>>
                  <?= $pp ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bx bx-search"></i>
              <span class="d-none d-md-inline">Filter</span>
            </button>
          </div>
        </form>

        <!-- Tabel Karir -->
        <?php if ($activeTab === 'careers'): ?>

          <?php if (! $hasCareerTable): ?>
            <div class="alert alert-info mb-0">
              Tabel <code>student_saved_careers</code> belum tersedia di database.
              Halaman ini akan menampilkan data setelah tabel tersebut dibuat
              dan siswa mulai menyimpan pilihan karir.
            </div>

          <?php elseif (empty($careerChoices)): ?>
            <div class="alert alert-warning mb-0">
              Belum ada siswa yang menyimpan pilihan karir sesuai filter.
            </div>

          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover align-middle">
                <thead>
                  <tr>
                    <th>Siswa</th>
                    <th>NIS</th>
                    <th>Kelas</th>
                    <th>Karir</th>
                    <th>Sektor</th>
                    <th>Min. Pendidikan</th>
                    <th>Disimpan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($careerChoices as $row): ?>
                    <tr>
                      <td><?= esc($row['student_name'] ?? '-') ?></td>
                      <td><?= esc($row['nis'] ?? '-') ?></td>
                      <td><?= esc($row['class_name'] ?? '-') ?></td>
                      <td><?= esc($row['career_title'] ?? '-') ?></td>
                      <td><?= esc($row['sector'] ?? '-') ?></td>
                      <td><?= esc($row['min_education'] ?? '-') ?></td>
                      <td><?= esc($row['saved_at'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <?php if ($careerPager): ?>
              <div class="mt-2">
                <?= $careerPager->links('student_careers', 'default_full') ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>

        <!-- Tabel Perguruan Tinggi -->
        <?php else: ?>

          <?php if (! $hasUnivTable): ?>
            <div class="alert alert-info mb-0">
              Tabel <code>student_saved_universities</code> belum tersedia di database.
              Halaman ini akan menampilkan data setelah tabel tersebut dibuat
              dan siswa mulai menyimpan perguruan tinggi.
            </div>

          <?php elseif (empty($universityChoices)): ?>
            <div class="alert alert-warning mb-0">
              Belum ada siswa yang menyimpan perguruan tinggi sesuai filter.
            </div>

          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover align-middle">
                <thead>
                  <tr>
                    <th>Siswa</th>
                    <th>NIS</th>
                    <th>Kelas</th>
                    <th>Perguruan Tinggi</th>
                    <th>Lokasi</th>
                    <th>Akreditasi</th>
                    <th>Disimpan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($universityChoices as $row): ?>
                    <tr>
                      <td><?= esc($row['student_name'] ?? '-') ?></td>
                      <td><?= esc($row['nis'] ?? '-') ?></td>
                      <td><?= esc($row['class_name'] ?? '-') ?></td>
                      <td><?= esc($row['university_name'] ?? '-') ?></td>
                      <td><?= esc($row['location'] ?? '-') ?></td>
                      <td><?= esc($row['accreditation'] ?? '-') ?></td>
                      <td><?= esc($row['saved_at'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <?php if ($universityPager): ?>
              <div class="mt-2">
                <?= $universityPager->links('student_universities', 'default_full') ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>

        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<?= $this->endSection() ?>
