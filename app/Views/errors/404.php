<div class="container py-5 text-center my-auto">
    <div class="py-5">
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <h3 class="fw-bold text-dark mb-2">Halaman Tidak Ditemukan</h3>
        <p class="text-secondary max-w-md mx-auto mb-4" style="max-width: 480px;">
            Alamat URI <code><?= e($requestedUri ?? '') ?></code> tidak ditemukan dalam rute aplikasi Smart Campus Cash.
        </p>
        <a href="<?= url('') ?>" class="btn btn-primary px-4 py-2 rounded-3">
            <i class="bi bi-house me-1"></i> Kembali ke Beranda
        </a>
    </div>
</div>
