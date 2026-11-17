<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Data Mahasiswa</h4>
        <p class="text-secondary small mb-0">Perbarui data identitas dan status akademik mahasiswa.</p>
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
        <form action="<?= url('admin/students/update?id=' . $student['id']) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nomor Induk Mahasiswa (NIM) <span class="text-danger">*</span></label>
                    <input type="text" name="nim" class="form-control font-monospace" value="<?= e($student['nim']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nama Lengkap Mahasiswa <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($student['full_name']) ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Fakultas <span class="text-danger">*</span></label>
                    <select name="faculty" class="form-select" required>
                        <option value="Teknik & Informatika" <?= ($student['faculty'] ?? '') === 'Teknik & Informatika' ? 'selected' : '' ?>>Fakultas Teknik &amp; Informatika</option>
                        <option value="Ekonomi & Bisnis" <?= ($student['faculty'] ?? '') === 'Ekonomi & Bisnis' ? 'selected' : '' ?>>Fakultas Ekonomi &amp; Bisnis</option>
                        <option value="Ilmu Sosial & Humaniora" <?= ($student['faculty'] ?? '') === 'Ilmu Sosial & Humaniora' ? 'selected' : '' ?>>Fakultas Ilmu Sosial &amp; Humaniora</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Program Studi <span class="text-danger">*</span></label>
                    <input type="text" name="study_program" class="form-control" value="<?= e($student['study_program']) ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Angkatan / Tahun Masuk <span class="text-danger">*</span></label>
                    <input type="text" name="academic_year" class="form-control" value="<?= e($student['academic_year']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Semester Saat Ini</label>
                    <input type="number" name="current_semester" class="form-control" value="<?= (int)$student['current_semester'] ?>" min="1" max="14">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Kelompok Kelas</label>
                    <input type="text" name="class_group" class="form-control" value="<?= e($student['class_group']) ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Email Mahasiswa</label>
                    <input type="email" name="email" class="form-control" value="<?= e($student['email'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($student['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Status Mahasiswa</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $student['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $student['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                        <option value="graduated" <?= $student['status'] === 'graduated' ? 'selected' : '' ?>>Lulus</option>
                        <option value="on_leave" <?= $student['status'] === 'on_leave' ? 'selected' : '' ?>>Cuti</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
                <a href="<?= url('admin/students') ?>" class="btn btn-light px-3">Batal</a>
            </div>
        </form>
    </div>
</div>
