<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/roles/edit.php
 *
 * Admin • Edit Peran & Izin
 * - Tampilan diseragamkan dengan gaya halaman Kelola Siswa.
 * - Izin dikelompokkan per fitur dengan label & keterangan bahasa Indonesia
 *   (PermissionModel::catalog()) supaya mudah dipahami orang awam.
 * - Nama peran bawaan sistem (id 1-6) dikunci; deskripsi & izin tetap bisa diubah.
 */

$role               = $role ?? [];
$groupedPermissions = $groupedPermissions ?? [];
$assignedIds        = array_map('intval', $assignedIds ?? []);
$userCount          = (int) ($userCount ?? 0);
$isBuiltin          = (bool) ($isBuiltin ?? false);

$roleId    = (int) ($role['id'] ?? 0);
$roleName  = (string) ($role['role_name'] ?? '');
$isAdmin   = $roleId === 1;

$totalPermissions = 0;
foreach ($groupedPermissions as $items) {
    $totalPermissions += count($items);
}

// Ikon per kelompok agar mudah dikenali sekilas
$groupIcons = [
    'Umum'                       => 'mdi-view-dashboard-outline',
    'Administrasi Sistem'        => 'mdi-cog-outline',
    'Data Akademik & Siswa'      => 'mdi-school-outline',
    'Konsultasi & Pengaduan'     => 'mdi-message-alert-outline',
    'Layanan BK'                 => 'mdi-account-heart-outline',
    'Penugasan'                  => 'mdi-clipboard-text-outline',
    'Asesmen'                    => 'mdi-clipboard-check-outline',
    'Info Karier & Studi Lanjut' => 'mdi-briefcase-outline',
    'Laporan'                    => 'mdi-file-chart-outline',
    'Lainnya'                    => 'mdi-dots-horizontal-circle-outline',
];
?>

<!-- Page Title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Edit Peran &amp; Izin</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/roles') ?>">Peran</a></li>
                    <li class="breadcrumb-item active"><?= esc($roleName) ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-2"></i>
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Kolom kiri: data peran + ringkasan -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="mdi mdi-shield-account-outline me-1"></i> Data Peran
                </h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/roles/update/' . $roleId) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="role_name" class="form-label">Nama Peran</label>
                        <input
                            type="text"
                            id="role_name"
                            name="role_name"
                            class="form-control"
                            value="<?= esc(old('role_name') ?? $roleName) ?>"
                            <?= $isBuiltin ? 'readonly' : 'required' ?>
                        >
                        <?php if ($isBuiltin): ?>
                            <div class="form-text">
                                <i class="mdi mdi-lock-outline me-1"></i>
                                Nama peran bawaan sistem tidak dapat diubah karena dipakai langsung oleh aplikasi.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="role_description" class="form-label">Deskripsi</label>
                        <textarea
                            id="role_description"
                            name="description"
                            rows="4"
                            class="form-control"
                            placeholder="Jelaskan singkat peran ini untuk apa"><?= esc(old('description') ?? ($role['description'] ?? '')) ?></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan Data Peran
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="mdi mdi-information-outline me-1"></i> Ringkasan
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Jenis peran</span>
                        <?php if ($isBuiltin): ?>
                            <span class="badge bg-info-subtle text-info"><i class="mdi mdi-shield-lock me-1"></i>Bawaan Sistem</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary">Peran Tambahan</span>
                        <?php endif; ?>
                    </li>
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Jumlah pengguna dengan peran ini</span>
                        <span class="badge bg-primary font-size-12"><?= number_format($userCount) ?> pengguna</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Izin dicentang</span>
                        <span class="badge bg-success font-size-12">
                            <span id="checkedCount"><?= count($assignedIds) ?></span> dari <?= $totalPermissions ?> izin
                        </span>
                    </li>
                </ul>

                <div class="alert alert-warning mb-0 mt-3 py-2 font-size-12">
                    <i class="mdi mdi-lightbulb-on-outline me-1"></i>
                    Perubahan izin <strong>langsung berlaku</strong> untuk semua pengguna dengan peran ini,
                    tanpa perlu keluar-masuk akun.
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom kanan: daftar izin per kelompok fitur -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0">
                    <i class="mdi mdi-key-variant me-1"></i>
                    Izin Akses — <?= esc($roleName) ?>
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" id="checkAll" class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-check-all me-1"></i>Centang Semua
                    </button>
                    <button type="button" id="uncheckAll" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-checkbox-blank-off-outline me-1"></i>Kosongkan
                    </button>
                </div>
            </div>

            <div class="card-body">
                <?php if ($isAdmin): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="mdi mdi-shield-crown me-1"></i>
                        Peran <strong>Admin</strong> otomatis memiliki <strong>seluruh izin</strong> di aplikasi.
                        Centangan di bawah tidak membatasi Admin; sebaiknya biarkan tercentang semua.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info py-2" role="alert">
                        <i class="mdi mdi-gesture-tap me-1"></i>
                        Centang kemampuan yang <strong>boleh</strong> dilakukan peran ini, lalu klik
                        <strong>Simpan Izin Akses</strong> di bagian bawah.
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <div class="position-relative">
                        <input type="text" class="form-control" id="permSearch"
                               placeholder="Cari izin... (contoh: laporan, pesan, asesmen)">
                    </div>
                </div>

                <form action="<?= base_url('admin/roles/assign-permissions/' . $roleId) ?>" method="post" id="permForm">
                    <?= csrf_field() ?>

                    <div id="permList">
                        <?php if (!empty($groupedPermissions)): ?>
                            <?php foreach ($groupedPermissions as $groupName => $perms): ?>
                                <?php $groupIcon = $groupIcons[$groupName] ?? 'mdi-folder-outline'; ?>
                                <div class="perm-group border rounded mb-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 bg-light rounded-top">
                                        <h6 class="mb-0">
                                            <i class="mdi <?= esc($groupIcon) ?> me-1 text-primary"></i>
                                            <?= esc($groupName) ?>
                                            <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($perms) ?> izin</span>
                                        </h6>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input group-toggle" type="checkbox" id="group-<?= esc(url_title($groupName, '-', true)) ?>">
                                            <label class="form-check-label font-size-12 text-muted" for="group-<?= esc(url_title($groupName, '-', true)) ?>">
                                                Centang semua di kelompok ini
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row g-0 p-2">
                                        <?php foreach ($perms as $perm):
                                            $pid     = (int) ($perm['id'] ?? 0);
                                            $pname   = (string) ($perm['permission_name'] ?? '');
                                            $plabel  = (string) ($perm['label'] ?? $pname);
                                            $pdesc   = (string) ($perm['keterangan'] ?? '');
                                            $checked = in_array($pid, $assignedIds, true) ? 'checked' : '';
                                            $needle  = strtolower($plabel . ' ' . $pdesc . ' ' . $pname);
                                        ?>
                                            <div class="col-md-6 perm-item p-2" data-search="<?= esc($needle, 'attr') ?>">
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input perm-check"
                                                        type="checkbox"
                                                        id="perm-<?= $pid ?>"
                                                        name="permissions[]"
                                                        value="<?= $pid ?>"
                                                        <?= $checked ?>
                                                    >
                                                    <label class="form-check-label d-block" for="perm-<?= $pid ?>">
                                                        <span class="fw-semibold d-block"><?= esc($plabel) ?></span>
                                                        <?php if ($pdesc !== ''): ?>
                                                            <span class="text-muted font-size-12 d-block"><?= esc($pdesc) ?></span>
                                                        <?php endif; ?>
                                                        <code class="font-size-11 text-muted"><?= esc($pname) ?></code>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="alert alert-warning d-none mb-3" id="noSearchResult">
                                <i class="mdi mdi-magnify-close me-1"></i>
                                Tidak ada izin yang cocok dengan kata kunci pencarian.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-3">Belum ada data izin.</div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-2">
                        <a class="btn btn-light" href="<?= base_url('admin/roles') ?>">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan Izin Akses
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const list         = document.getElementById('permList');
    const searchInput  = document.getElementById('permSearch');
    const checkAllBtn  = document.getElementById('checkAll');
    const uncheckAllBtn = document.getElementById('uncheckAll');
    const counterEl    = document.getElementById('checkedCount');
    const noResultEl   = document.getElementById('noSearchResult');

    if (!list) return;

    function allChecks() {
        return list.querySelectorAll('input.perm-check');
    }

    function updateCounter() {
        if (!counterEl) return;
        counterEl.textContent = list.querySelectorAll('input.perm-check:checked').length;
    }

    // Sinkronkan status checkbox "Centang semua di kelompok ini"
    function syncGroupToggles() {
        list.querySelectorAll('.perm-group').forEach(function (group) {
            const toggle = group.querySelector('.group-toggle');
            if (!toggle) return;
            const checks = group.querySelectorAll('input.perm-check');
            const checkedCount = group.querySelectorAll('input.perm-check:checked').length;
            toggle.checked = checks.length > 0 && checkedCount === checks.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < checks.length;
        });
    }

    // Tombol centang semua / kosongkan (global)
    if (checkAllBtn) checkAllBtn.addEventListener('click', function () {
        allChecks().forEach(cb => cb.checked = true);
        syncGroupToggles();
        updateCounter();
    });
    if (uncheckAllBtn) uncheckAllBtn.addEventListener('click', function () {
        allChecks().forEach(cb => cb.checked = false);
        syncGroupToggles();
        updateCounter();
    });

    // Toggle per kelompok
    list.querySelectorAll('.group-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const group = toggle.closest('.perm-group');
            group.querySelectorAll('input.perm-check').forEach(cb => cb.checked = toggle.checked);
            updateCounter();
        });
    });

    // Update ringkasan saat centang manual
    list.addEventListener('change', function (e) {
        if (e.target.classList.contains('perm-check')) {
            syncGroupToggles();
            updateCounter();
        }
    });

    // Pencarian: sembunyikan item yang tidak cocok; kelompok tanpa hasil ikut disembunyikan
    if (searchInput) searchInput.addEventListener('input', function (e) {
        const q = e.target.value.trim().toLowerCase();
        let anyVisible = false;

        list.querySelectorAll('.perm-group').forEach(function (group) {
            let groupVisible = false;
            group.querySelectorAll('.perm-item').forEach(function (item) {
                const hit = (item.getAttribute('data-search') || '').includes(q);
                item.style.display = hit ? '' : 'none';
                if (hit) groupVisible = true;
            });
            group.style.display = groupVisible ? '' : 'none';
            if (groupVisible) anyVisible = true;
        });

        if (noResultEl) noResultEl.classList.toggle('d-none', anyVisible);
    });

    syncGroupToggles();
    updateCounter();

    // Auto-hide alert flash
    setTimeout(function () {
        document.querySelectorAll('.alert-success.alert-dismissible, .alert-danger.alert-dismissible').forEach(function (el) {
            el.style.transition = 'opacity .6s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 700);
        });
    }, 5000);
})();
</script>
<?= $this->endSection() ?>
