<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Pengaturan Sistem</h4>
        <p class="text-secondary small mb-0">Konfigurasi parameter operasional dan kebijakan keamanan Smart Campus Cash.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form onsubmit="alert('Pengaturan sistem berhasil disimpan.'); return false;">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-sliders me-1"></i> Parameter Umum</h6>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Aplikasi</label>
                        <input type="text" class="form-control" value="Smart Campus Cash" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tahun Akademik Berjalan</label>
                        <input type="text" class="form-control" value="2026/2027 Gasal">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Format Nomor Invoice Default</label>
                        <input type="text" class="form-control font-monospace" value="INV/{YEAR}/{MONTH}/{XXXX}" readonly>
                        <small class="text-muted">Nomor urut otomatis 4 digit per bulan.</small>
                    </div>

                    <h6 class="fw-bold text-primary mt-4 mb-3"><i class="bi bi-shield-lock me-1"></i> Keamanan &amp; Sesi</h6>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="secSessFix" checked disabled>
                        <label class="form-check-label small" for="secSessFix">Regenerasi ID Sesi saat Login (Session Fixation Defense - Aktif)</label>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="secHttpOnly" checked disabled>
                        <label class="form-check-label small" for="secHttpOnly">Cookie Sesi HttpOnly &amp; SameSite Lax (XSS Defense - Aktif)</label>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="secRbac" checked disabled>
                        <label class="form-check-label small" for="secRbac">Strict RBAC Backend Guarding (Bypass Defense - Aktif)</label>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 bg-light-subtle border-start border-primary border-3">
            <div class="card-body p-3.5">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle text-primary me-1"></i> Izin Pengaturan</h6>
                <p class="small text-muted mb-0">
                    Modul ini eksklusif bagi role <strong>Administrator</strong>. Role Kasir, Mahasiswa, dan Bagian Keuangan tidak memiliki hak akses mengubah konfigurasi sistem ini sesuai spesifikasi PRD RBAC.
                </p>
            </div>
        </div>
    </div>
</div>
