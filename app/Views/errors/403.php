<div class="container py-5 text-center my-auto">
    <div class="py-5">
        <div class="mb-3">
            <div class="p-3 rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 84px; height: 84px;">
                <i class="bi bi-shield-x fs-1"></i>
            </div>
        </div>
        <h1 class="display-4 fw-bold text-danger mb-2">403 Akses Ditolak</h1>
        <h5 class="fw-semibold text-dark mb-3">Anda Tidak Memiliki Izin untuk Mengakses Halaman Ini</h5>
        
        <div class="card border-0 shadow-sm rounded-3 max-w-lg mx-auto p-4 mb-4 text-start bg-white" style="max-width: 540px;">
            <div class="d-flex justify-content-between py-1 border-bottom small">
                <span class="text-muted">Peran Anda Saat Ini:</span>
                <span class="badge bg-primary-subtle text-primary fw-bold text-capitalize"><?= e($userRole ?? 'Guest') ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom small">
                <span class="text-muted">Peran yang Diizinkan:</span>
                <span class="badge bg-danger-subtle text-danger fw-bold"><?= e(implode(', ', (array)($allowedRoles ?? []))) ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
                <span class="text-muted">Target URL:</span>
                <code class="text-secondary"><?= e($attemptedUrl ?? '') ?></code>
            </div>
            <p class="small text-muted mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i> Sistem menerapkan <strong>Role-Based Access Control (RBAC)</strong> tingkat backend. Akses langsung melalui URL atau parameter tanpa otorisasi ditolak dan dicatat di log audit keamanan.
            </p>
        </div>

        <div class="d-flex justify-content-center gap-2">
            <a href="<?= \App\Services\AuthService::getRedirectForRole() ?>" class="btn btn-primary px-4 py-2 rounded-2">
                <i class="bi bi-house-door me-1"></i> Kembali ke Dashboard Saya
            </a>
            <a href="<?= url('logout') ?>" class="btn btn-outline-danger px-3 py-2 rounded-2">
                <i class="bi bi-box-arrow-right me-1"></i> Ganti Akun
            </a>
        </div>
    </div>
</div>
