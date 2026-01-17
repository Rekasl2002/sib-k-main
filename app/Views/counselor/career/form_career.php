<?php
/**
 * File Path: app/Views/counselor/career/form_career.php
 *
 * Variabel:
 * - $career (array|null)
 * - $errors (array)
 * - $mode   ('create'|'edit')
 */
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$errors = $errors ?? [];
$isEdit = ($mode ?? 'create') === 'edit';

function oldv($key, $default = '')
{
    $old = old($key);
    return $old !== null ? $old : $default;
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
        <i class="mdi mdi-briefcase-outline me-2"></i>
        <?= $isEdit ? 'Edit Pilihan Karir' : 'Tambah Pilihan Karir' ?>
    </h4>
    <a href="<?= site_url('counselor/career-info?tab=careers') ?>" class="btn btn-sm btn-secondary">
        &larr; Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post"
              action="<?= $isEdit
                  ? route_to('counselor.career.update', $career['id'])
                  : route_to('counselor.career.store') ?>">

            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Judul Karir <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= esc(oldv('title', $career['title'] ?? '')) ?>">
                <?php if (!empty($errors['title'])): ?>
                    <div class="text-danger small"><?= esc($errors['title']) ?></div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="mb-3 col-md-4">
                    <label class="form-label">Sektor</label>
                    <input type="text" name="sector" class="form-control"
                           placeholder="contoh: Teknologi Informasi"
                           value="<?= esc(oldv('sector', $career['sector'] ?? '')) ?>">
                    <?php if (!empty($errors['sector'])): ?>
                        <div class="text-danger small"><?= esc($errors['sector']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 col-md-4">
                    <label class="form-label">Min. Pendidikan</label>
                    <select name="min_education" class="form-select">
                        <option value="">- Pilih -</option>
                        <?php foreach (['SMA/SMK','D3','S1','S2'] as $e): ?>
                            <option value="<?= esc($e) ?>"
                                <?= oldv('min_education', $career['min_education'] ?? '') === $e ? 'selected' : '' ?>>
                                <?= esc($e) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['min_education'])): ?>
                        <div class="text-danger small"><?= esc($errors['min_education']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 col-md-4">
                    <label class="form-label">Permintaan (0-10)</label>
                    <input type="number" min="0" max="10" name="demand_level" class="form-control"
                           value="<?= esc(oldv('demand_level', $career['demand_level'] ?? 0)) ?>">
                    <?php if (!empty($errors['demand_level'])): ?>
                        <div class="text-danger small"><?= esc($errors['demand_level']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Perkiraan Gaji per Bulan (IDR)</label>
                <input type="number" name="avg_salary_idr" class="form-control"
                       value="<?= esc(oldv('avg_salary_idr', $career['avg_salary_idr'] ?? '')) ?>">
                <?php if (!empty($errors['avg_salary_idr'])): ?>
                    <div class="text-danger small"><?= esc($errors['avg_salary_idr']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Deskripsi / Gambaran Pekerjaan <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="4"><?= esc(oldv('description', $career['description'] ?? '')) ?></textarea>
                <?php if (!empty($errors['description'])): ?>
                    <div class="text-danger small"><?= esc($errors['description']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Keahlian yang Disarankan</label>
                <div id="skills-wrapper">
                    <?php
                    $skills = old('skills');
                    if ($skills === null) {
                        $skills = $career['required_skills_array'] ?? [''];
                    }
                    if (empty($skills)) {
                        $skills = [''];
                    }
                    ?>
                    <?php foreach ((array)$skills as $idx => $skill): ?>
                        <div class="input-group mb-2 skill-row">
                            <input type="text" name="skills[]" class="form-control"
                                   placeholder="contoh: Pemrograman, Komunikasi"
                                   value="<?= esc($skill) ?>">
                            <button class="btn btn-outline-danger btn-remove-skill" type="button">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-skill">
                    <i class="mdi mdi-plus"></i> Tambah Keahlian
                </button>
            </div>

            <div class="mb-3">
                <label class="form-label">Jalur Pengembangan / Pathway</label>
                <textarea name="pathways" class="form-control" rows="3"
                          placeholder="contoh: SMK TI → Magang → Junior Developer → ..."><?= esc(oldv('pathways', $career['pathways'] ?? '')) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Link Referensi Eksternal</label>
                <div id="links-wrapper">
                    <?php
                    $linksOld = old('links_url');
                    $links    = [];
                    if ($linksOld !== null) {
                        $urlOld   = (array) old('links_url');
                        $labelOld = (array) old('links_label');
                        foreach ($urlOld as $i => $u) {
                            $links[] = ['url' => $u, 'label' => $labelOld[$i] ?? ''];
                        }
                    } else {
                        $links = $career['external_links_array'] ?? [['url' => '', 'label' => '']];
                    }
                    if (empty($links)) {
                        $links = [['url' => '', 'label' => '']];
                    }
                    ?>
                    <?php foreach ($links as $lnk): ?>
                        <div class="row g-2 mb-2 link-row">
                            <div class="col-md-6">
                                <input type="url" name="links_url[]" class="form-control"
                                       placeholder="https://..."
                                       value="<?= esc($lnk['url'] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="links_label[]" class="form-control"
                                       placeholder="Label (optional)"
                                       value="<?= esc($lnk['label'] ?? '') ?>">
                            </div>
                            <div class="col-md-1 d-flex align-items-center">
                                <button class="btn btn-outline-danger btn-remove-link" type="button">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-link">
                    <i class="mdi mdi-plus"></i> Tambah Link
                </button>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-select">
                    <?php $val = (int) oldv('is_active', $career['is_active'] ?? 1); ?>
                    <option value="1" <?= $val === 1 ? 'selected' : '' ?>>Aktif (ditampilkan ke siswa)</option>
                    <option value="0" <?= $val === 0 ? 'selected' : '' ?>>Nonaktif</option>
                </select>
                <?php if (!empty($errors['is_active'])): ?>
                    <div class="text-danger small"><?= esc($errors['is_active']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Publikasi</label>
                <?php $pub = (int) oldv('is_public', $career['is_public'] ?? 0); ?>
                <select name="is_public" class="form-select">
                    <option value="0" <?= $pub === 0 ? 'selected' : '' ?>>Private (belum tayang)</option>
                    <option value="1" <?= $pub === 1 ? 'selected' : '' ?>>Published (tampil di portal)</option>
                </select>
                <?php if (!empty($errors['is_public'])): ?>
                    <div class="text-danger small"><?= esc($errors['is_public']) ?></div>
                <?php endif; ?>
            </div>

            <?php if ($isEdit): ?>
                <div class="mb-3">
                    <label class="form-label">Informasi internal</label>
                    <div class="form-text">
                        <?php
                        $creatorName = trim((string)($career['created_by_name'] ?? ''));

                        if ($creatorName !== '') {
                            echo 'Dibuat oleh: ' . esc($creatorName);
                        } else {
                            echo 'Informasi pembuat belum tersedia.';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const skillsWrapper = document.getElementById('skills-wrapper');
    const btnAddSkill   = document.getElementById('btn-add-skill');
    const linksWrapper  = document.getElementById('links-wrapper');
    const btnAddLink    = document.getElementById('btn-add-link');

    if (btnAddSkill && skillsWrapper) {
        btnAddSkill.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'input-group mb-2 skill-row';
            row.innerHTML = `
                <input type="text" name="skills[]" class="form-control" placeholder="contoh: Pemrograman, Komunikasi">
                <button class="btn btn-outline-danger btn-remove-skill" type="button">
                    <i class="mdi mdi-close"></i>
                </button>
            `;
            skillsWrapper.appendChild(row);
        });

        skillsWrapper.addEventListener('click', function (e) {
            if (e.target.closest('.btn-remove-skill')) {
                const row = e.target.closest('.skill-row');
                if (row && skillsWrapper.querySelectorAll('.skill-row').length > 1) {
                    row.remove();
                }
            }
        });
    }

    if (btnAddLink && linksWrapper) {
        btnAddLink.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 link-row';
            row.innerHTML = `
                <div class="col-md-6">
                    <input type="url" name="links_url[]" class="form-control" placeholder="https://...">
                </div>
                <div class="col-md-5">
                    <input type="text" name="links_label[]" class="form-control" placeholder="Label (optional)">
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <button class="btn btn-outline-danger btn-remove-link" type="button">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
            `;
            linksWrapper.appendChild(row);
        });

        linksWrapper.addEventListener('click', function (e) {
            if (e.target.closest('.btn-remove-link')) {
                const row = e.target.closest('.link-row');
                if (row && linksWrapper.querySelectorAll('.link-row').length > 1) {
                    row.remove();
                }
            }
        });
    }
});
</script>

<?= $this->endSection() ?>
