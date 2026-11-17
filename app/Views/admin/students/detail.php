<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Profil Mahasiswa: <?= e($student['full_name']) ?></h4>
        <p class="text-secondary small mb-0">Rincian data identitas, status perkuliahan, dan keterkaitan akun sistem.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/students/edit?id=' . $student['id']) ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Data
        </a>
        <a href="<?= url('admin/students') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Student Avatar & Basic Badge -->
        <div class="card border-0 shadow-sm rounded-3 text-center p-4">
            <div class="mx-auto mb-3 rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 80px; height: 80px;">
                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
            </div>
            <h5 class="fw-bold text-dark mb-1"><?= e($student['full_name']) ?></h5>
            <span class="font-monospace text-primary fw-semibold d-block mb-2">NIM: <?= e($student['nim']) ?></span>
            <div class="mb-3">
                <?php if ($student['status'] === 'active'): ?>
                    <span class="badge bg-success-subtle text-success px-3 py-1.5 rounded-pill">Status: Aktif</span>
                <?php elseif ($student['status'] === 'graduated'): ?>
                    <span class="badge bg-primary-subtle text-primary px-3 py-1.5 rounded-pill">Status: Lulus</span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary px-3 py-1.5 rounded-pill">Status: <?= e($student['status']) ?></span>
                <?php endif; ?>
            </div>
            <div class="text-muted small border-top pt-3 text-start">
                <div class="d-flex justify-content-between mb-1">
                    <span>Program Studi:</span>
                    <strong class="text-dark"><?= e($student['study_program']) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Fakultas:</span>
                    <span class="text-dark"><?= e($student['faculty'] ?? 'Teknik & Informatika') ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Angkatan:</span>
                    <span class="text-dark"><?= e($student['academic_year']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Detail Akademik & Kontak -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-lines-fill text-primary me-1"></i> Data Kontak &amp; Akademik</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Email Mahasiswa</small>
                        <strong class="text-dark"><?= e($student['email'] ?: 'Belum diisi') ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nomor Telepon / WhatsApp</small>
                        <strong class="text-dark"><?= e($student['phone'] ?: 'Belum diisi') ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Semester Aktif</small>
                        <span class="badge bg-light text-dark border">Semester <?= (int)$student['current_semester'] ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Kelompok Kelas</small>
                        <span class="badge bg-light text-dark border">Kelas <?= e($student['class_group']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Akun Pengguna Tertaut -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shield-check text-success me-1"></i> Akun Pengguna Tertaut</h6>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($student['username'])): ?>
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border">
                        <div>
                            <div class="fw-bold text-dark">@<?= e($student['username']) ?></div>
                            <small class="text-muted">Email Akun: <?= e($student['user_email']) ?></small>
                        </div>
                        <span class="badge bg-success-subtle text-success">Akun Aktif</span>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Mahasiswa ini belum memiliki akun user login terpisah.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
