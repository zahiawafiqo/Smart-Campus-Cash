<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tambah Mahasiswa Baru</h4>
        <p class="text-secondary small mb-0">Input data identitas dan status akademik mahasiswa baru.</p>
    </div>
    <a href="<?= url('admin/students') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 max-w-3xl">
    <div class="card-body p-4">
        <form action="<?= url('admin/students') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nomor Induk Mahasiswa (NIM) <span class="text-danger">*</span></label>
                    <input type="text" name="nim" class="form-control font-monospace" value="<?= e($old['nim'] ?? '') ?>" placeholder="Contoh: 2110114005" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nama Lengkap Mahasiswa <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name'] ?? '') ?>" placeholder="Contoh: Rina Anggraini" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Fakultas <span class="text-danger">*</span></label>
                    <select name="faculty" class="form-select" required>
                        <option value="Teknik & Informatika">Fakultas Teknik &amp; Informatika</option>
                        <option value="Ekonomi & Bisnis">Fakultas Ekonomi &amp; Bisnis</option>
                        <option value="Ilmu Sosial & Humaniora">Fakultas Ilmu Sosial &amp; Humaniora</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Program Studi <span class="text-danger">*</span></label>
                    <input type="text" name="study_program" class="form-control" value="<?= e($old['study_program'] ?? 'Teknik Informatika') ?>" placeholder="Contoh: Teknik Informatika / Sistem Informasi" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Angkatan / Tahun Masuk <span class="text-danger">*</span></label>
                    <input type="text" name="academic_year" class="form-control" value="<?= e($old['academic_year'] ?? '2024/2025') ?>" placeholder="Contoh: 2024/2025" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Semester Saat Ini</label>
                    <input type="number" name="current_semester" class="form-control" value="<?= (int)($old['current_semester'] ?? 1) ?>" min="1" max="14">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Kelompok Kelas</label>
                    <input type="text" name="class_group" class="form-control" value="<?= e($old['class_group'] ?? 'TI-01A') ?>" placeholder="Contoh: TI-01A">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Email Mahasiswa</label>
                    <input type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>" placeholder="student@campus.ac.id">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($old['phone'] ?? '') ?>" placeholder="08123456789">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Status Mahasiswa</label>
                    <select name="status" class="form-select">
                        <option value="active" selected>Aktif</option>
                        <option value="inactive">Nonaktif</option>
                        <option value="on_leave">Cuti Kuliah</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Data Mahasiswa
                </button>
                <a href="<?= url('admin/students') ?>" class="btn btn-light px-3">Batal</a>
            </div>
        </form>
    </div>
</div>
