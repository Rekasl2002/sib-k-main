<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card mt-4">
      <div class="card-body text-center py-5">
        <i class="mdi mdi-check-decagram text-success" style="font-size:64px;"></i>
        <h3 class="mt-3">Terima Kasih</h3>
        <p class="text-muted mb-1">
          Jawaban evaluasi prototipe Anda telah tersimpan.
        </p>
        <p class="text-muted">
          Masukan Anda sangat berharga bagi pengembangan aplikasi SIB-K di MA Persis 31 Banjaran.<br>
          Jazaakumullahu khairan.
        </p>
        <p class="mb-4">Wassalamu&rsquo;alaikum warahmatullahi wabarakatuh.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="<?= base_url('prototype?role=' . rawurlencode($roleMode ?? '')) ?>" class="btn btn-outline-primary">
            <i class="mdi mdi-view-grid-outline me-1"></i> Kembali ke Prototipe
          </a>
          <a href="<?= base_url('dashboard') ?>" class="btn btn-light">
            <i class="mdi mdi-home-outline me-1"></i> Dashboard
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
