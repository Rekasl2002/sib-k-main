<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/**
 * File Path: app/Views/admin/roles/create.php
 *
 * Admin • Tambah Peran
 * - Tampilan diseragamkan dengan gaya halaman Kelola Siswa.
 * - Izin dikelompokkan per fitur dengan label & keterangan bahasa Indonesia
 *   (PermissionModel::catalog()) supaya mudah dipahami orang awam.
 */

$groupedPermissions = $groupedPermissions ?? [];

$oldVal     = old('permissions'); // bisa array|null|string
$oldChecked = is_array($oldVal) ? array_map('intval', $oldVal) : [];

$totalPermissions = 0;
foreach ($groupedPermissions as $items) {
    $totalPermissions += count($items);
}

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
            <h4 class="mb-0">Tambah Peran</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/roles') ?>">Peran</a></li>
                    <li class="breadcrumb-item active">Tambah Peran</li>
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

<form method="post" action="<?= route_to('admin.roles.store') ?>">
    <?= csrf_field() ?>

    <div class="row">
        <!-- Kolom kiri: data peran -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-shield-account-outline me-1"></i> Data Peran
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Nama Peran <span class="text-danger">*</span></label>
                        <input type="text" id="role_name" name="role_name" class="form-control" required
                               placeholder="Contoh: Staf Tata Usaha"
                               value="<?= esc(old('role_name')) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="role_description" class="form-label">Deskripsi</label>
                        <textarea id="role_description" name="description" class="form-control" rows="4"
                                  placeholder="Jelaskan singkat peran ini untuk apa"><?= esc(old('description')) ?></textarea>
                    </div>

                    <div class="alert alert-info py-2 font-size-12 mb-0">
                        <i class="mdi mdi-information-outline me-1"></i>
                        Setelah peran dibuat, pengguna dapat diberi peran ini melalui menu <strong>Kelola Pengguna</strong>.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Izin dicentang</span>
                        <span class="badge bg-success font-size-12">
                            <span id="checkedCount">0</span> dari <?= $totalPermissions ?> izin
                        </span>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan Peran Baru
                        </button>
                        <a href="<?= route_to('admin.roles') ?>" class="btn btn-light">
                            <i class="mdi mdi-arrow-left me-1"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: daftar izin per kelompok fitur -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-key-variant me-1"></i> Izin Akses Peran Baru
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
                    <div class="alert alert-info py-2" role="alert">
                        <i class="mdi mdi-gesture-tap me-1"></i>
                        Centang kemampuan yang <strong>boleh</strong> dilakukan peran baru ini.
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" id="permSearch"
                               placeholder="Cari izin... (contoh: laporan, pesan, asesmen)">
                    </div>

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
                                            $checked = in_array($pid, $oldChecked, true) ? 'checked' : '';
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

                            <div class="alert alert-warning d-none mb-0" id="noSearchResult">
                                <i class="mdi mdi-magnify-close me-1"></i>
                                Tidak ada izin yang cocok dengan kata kunci pencarian.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">Belum ada data izin.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const list          = document.getElementById('permList');
    const searchInput   = document.getElementById('permSearch');
    const checkAllBtn   = document.getElementById('checkAll');
    const uncheckAllBtn = document.getElementById('uncheckAll');
    const counterEl     = document.getElementById('checkedCount');
    const noResultEl    = document.getElementById('noSearchResult');

    if (!list) return;

    function allChecks() {
        return list.querySelectorAll('input.perm-check');
    }

    function updateCounter() {
        if (!counterEl) return;
        counterEl.textContent = list.querySelectorAll('input.perm-check:checked').length;
    }

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

    list.querySelectorAll('.group-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const group = toggle.closest('.perm-group');
            group.querySelectorAll('input.perm-check').forEach(cb => cb.checked = toggle.checked);
            updateCounter();
        });
    });

    list.addEventListener('change', function (e) {
        if (e.target.classList.contains('perm-check')) {
            syncGroupToggles();
            updateCounter();
        }
    });

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
})();
</script>
<?= $this->endSection() ?>
