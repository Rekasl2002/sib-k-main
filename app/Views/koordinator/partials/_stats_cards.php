<div class="row">
  <div class="col-md-3">
    <div class="card bg-role-koordinator mb-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">Total Siswa</h6>
        <h3 class="mb-0"><?= esc($stats['totalStudents'] ?? 0) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-role-koordinator mb-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">Total Staf (BK & Wali)</h6>
        <h3 class="mb-0"><?= esc($stats['totalStaff'] ?? 0) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-role-koordinator mb-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">Kasus Aktif</h6>
        <h3 class="mb-0"><?= esc($stats['activeCases'] ?? 0) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-role-koordinator mb-4">
      <div class="card-body">
        <h6 class="text-muted mb-1">Total Sesi</h6>
        <h3 class="mb-0"><?= esc($stats['totalSessions'] ?? 0) ?></h3>
      </div>
    </div>
  </div>
</div>