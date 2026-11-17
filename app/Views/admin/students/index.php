<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Master Data Mahasiswa</h4>
        <p class="text-secondary small mb-0">Pengelolaan direktori mahasiswa, program studi, status akademik, dan ringkasan tagihan.</p>
    </div>
    <button type="button" id="btnTambahStudent" class="btn btn-primary btn-sm" onclick="openStudentModal('tambah')">
        <i class="bi bi-person-plus me-1"></i> Tambah Mahasiswa Baru
    </button>
</div>

<?php if (!empty($success)): ?>
    <?php render_component('alert', ['type' => 'success', 'message' => $success, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('admin/students') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari NIM, Nama, atau Email..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="study_program" class="form-select form-select-sm">
                    <option value="">Semua Program Studi</option>
                    <?php foreach ($prodiList as $p): ?>
                        <option value="<?= e($p) ?>" <?= $selectedProdi === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="academic_year" class="form-select form-select-sm">
                    <option value="">Semua Angkatan</option>
                    <?php foreach ($yearList as $y): ?>
                        <option value="<?= e($y) ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                    <option value="graduated" <?= $selectedStatus === 'graduated' ? 'selected' : '' ?>>Lulus</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter"><i class="bi bi-funnel"></i></button>
                <a href="<?= url('admin/students') ?>" class="btn btn-sm btn-light" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Students -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>NIM &amp; Mahasiswa</th>
                    <th>Fakultas &amp; Program Studi</th>
                    <th>Angkatan / Kelas</th>
                    <th>Status Tunggakan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data mahasiswa yang sesuai kriteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <a href="<?= url('admin/students/detail?id=' . $s['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                    <?= e($s['full_name']) ?>
                                </a>
                                <div class="text-muted font-monospace small">NIM: <?= e($s['nim']) ?></div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><?= e($s['study_program']) ?></div>
                                <small class="text-muted"><?= e($s['faculty'] ?? 'Teknik & Informatika') ?></small>
                            </td>
                            <td>
                                <div><?= e($s['academic_year']) ?></div>
                                <small class="text-muted">Sem. <?= (int)$s['current_semester'] ?> (Kelas <?= e($s['class_group']) ?>)</small>
                            </td>
                            <td>
                                <?php if ((int)$s['unpaid_bills_count'] > 0): ?>
                                    <span class="badge bg-danger-subtle text-danger mb-1 d-block w-fit">
                                        <?= (int)$s['unpaid_bills_count'] ?> Tagihan Tertunggak
                                    </span>
                                    <small class="text-danger fw-semibold">Rp <?= number_format((float)$s['total_tunggakan'], 0, ',', '.') ?></small>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-all me-1"></i>Lunas / Bebas Tagihan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                <?php elseif ($s['status'] === 'graduated'): ?>
                                    <span class="badge bg-primary-subtle text-primary">Lulus</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= e($s['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('admin/students/detail?id=' . $s['id']) ?>" class="btn btn-outline-secondary" title="Detail Profil">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary" title="Edit Data"
                                            onclick="openStudentModal('edit', {
                                                id: <?= (int)$s['id'] ?>,
                                                nim: <?= json_encode($s['nim']) ?>,
                                                full_name: <?= json_encode($s['full_name']) ?>,
                                                faculty: <?= json_encode($s['faculty'] ?? 'Teknik & Informatika') ?>,
                                                study_program: <?= json_encode($s['study_program']) ?>,
                                                academic_year: <?= json_encode($s['academic_year']) ?>,
                                                current_semester: <?= (int)$s['current_semester'] ?>,
                                                class_group: <?= json_encode($s['class_group'] ?? 'A') ?>,
                                                email: <?= json_encode($s['email'] ?? '') ?>,
                                                phone: <?= json_encode($s['phone'] ?? '') ?>,
                                                status: <?= json_encode($s['status']) ?>
                                            })">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="<?= url('admin/students/delete?id=' . $s['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus data mahasiswa <?= e($s['full_name']) ?>? Data tagihan dan riwayat akan diarsipkan secara aman.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="Hapus (Soft Delete)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top">
            <?php
            render_component('pagination', [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'baseUrl' => url('admin/students?search=' . urlencode($search) . '&study_program=' . urlencode($selectedProdi) . '&academic_year=' . urlencode($selectedYear) . '&status=' . urlencode($selectedStatus)),
                'totalItems' => $total,
                'perPage' => $perPage
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- ================================================================
     MODAL: Tambah / Edit Mahasiswa
     Pola Bootstrap 5 modal konsisten dengan master kategori & manajemen pengguna.
     ================================================================ -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="studentModalLabel">
                    <i class="bi bi-person-plus text-primary me-1" id="studentModalIcon"></i>
                    <span id="studentModalTitle">Tambah Mahasiswa Baru</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup" id="studentModalCloseBtn"></button>
            </div>

            <form id="studentModalForm" method="POST" action="<?= url('admin/students') ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_from_modal" value="1">
                <input type="hidden" name="_edit_id" id="student_modal_edit_id" value="">

                <div class="modal-body py-3">
                    <?php if (!empty($error) && !empty($_SESSION['modal_mode'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 small py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="student_nim" class="form-label small fw-semibold">
                                Nomor Induk Mahasiswa (NIM) <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="student_nim" name="nim" class="form-control font-monospace"
                                   placeholder="Contoh: 2110114005" required minlength="4"
                                   value="<?= e($_SESSION['old_input']['nim'] ?? '') ?>">
                            <div class="invalid-feedback">NIM wajib diisi minimal 4 karakter.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="student_full_name" class="form-label small fw-semibold">
                                Nama Lengkap Mahasiswa <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="student_full_name" name="full_name" class="form-control"
                                   placeholder="Contoh: Rina Anggraini" required
                                   value="<?= e($_SESSION['old_input']['full_name'] ?? '') ?>">
                            <div class="invalid-feedback">Nama lengkap mahasiswa wajib diisi.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="student_faculty" class="form-label small fw-semibold">
                                Fakultas <span class="text-danger">*</span>
                            </label>
                            <select id="student_faculty" name="faculty" class="form-select" required>
                                <option value="Teknik & Informatika">Fakultas Teknik &amp; Informatika</option>
                                <option value="Ekonomi & Bisnis">Fakultas Ekonomi &amp; Bisnis</option>
                                <option value="Ilmu Sosial & Humaniora">Fakultas Ilmu Sosial &amp; Humaniora</option>
                            </select>
                            <div class="invalid-feedback">Pilih fakultas mahasiswa.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="student_study_program" class="form-label small fw-semibold">
                                Program Studi <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="student_study_program" name="study_program" class="form-control"
                                   placeholder="Contoh: Teknik Informatika / Sistem Informasi" required
                                   value="<?= e($_SESSION['old_input']['study_program'] ?? 'Teknik Informatika') ?>">
                            <div class="invalid-feedback">Program studi wajib diisi.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="student_academic_year" class="form-label small fw-semibold">
                                Angkatan / Tahun Masuk <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="student_academic_year" name="academic_year" class="form-control"
                                   placeholder="Contoh: 2024/2025" required
                                   value="<?= e($_SESSION['old_input']['academic_year'] ?? '2024/2025') ?>">
                            <div class="invalid-feedback">Angkatan/Tahun Masuk wajib diisi.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="student_current_semester" class="form-label small fw-semibold">
                                Semester Saat Ini
                            </label>
                            <input type="number" id="student_current_semester" name="current_semester" class="form-control"
                                   min="1" max="14" value="<?= (int)($_SESSION['old_input']['current_semester'] ?? 1) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="student_class_group" class="form-label small fw-semibold">
                                Kelompok Kelas
                            </label>
                            <input type="text" id="student_class_group" name="class_group" class="form-control"
                                   placeholder="Contoh: TI-01A"
                                   value="<?= e($_SESSION['old_input']['class_group'] ?? 'TI-01A') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label for="student_email" class="form-label small fw-semibold">
                                Email Mahasiswa
                            </label>
                            <input type="email" id="student_email" name="email" class="form-control"
                                   placeholder="student@campus.ac.id"
                                   value="<?= e($_SESSION['old_input']['email'] ?? '') ?>">
                            <div class="invalid-feedback">Format email tidak valid.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="student_phone" class="form-label small fw-semibold">
                                Nomor Telepon / WhatsApp
                            </label>
                            <input type="text" id="student_phone" name="phone" class="form-control"
                                   placeholder="08123456789"
                                   value="<?= e($_SESSION['old_input']['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="student_status" class="form-label small fw-semibold">Status Mahasiswa</label>
                            <select id="student_status" name="status" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="graduated">Lulus</option>
                                <option value="on_leave">Cuti Kuliah</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" id="studentModalCancelBtn">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3" id="studentModalSubmitBtn">
                        <span id="studentModalSubmitIcon" class="me-1"><i class="bi bi-check2-circle"></i></span>
                        <span id="studentModalSubmitText">Simpan Data Mahasiswa</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    let studentModalInstance = null;
    let formDirty = false;
    const form = document.getElementById('studentModalForm');

    function getStudentModal() {
        if (!studentModalInstance) {
            studentModalInstance = new bootstrap.Modal(document.getElementById('studentModal'), {
                backdrop: 'static',
                keyboard: false
            });
        }
        return studentModalInstance;
    }

    if (form) {
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.addEventListener('input', function () { formDirty = true; });
            el.addEventListener('change', function () { formDirty = true; });
        });
    }

    document.getElementById('studentModal').addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            tryCloseStudentModal();
        }
    });

    document.getElementById('studentModalCloseBtn').addEventListener('click', tryCloseStudentModal);
    document.getElementById('studentModalCancelBtn').addEventListener('click', tryCloseStudentModal);

    function tryCloseStudentModal() {
        if (formDirty) {
            if (!confirm('Data yang sudah Anda isi belum disimpan. Tutup dan buang perubahan?')) {
                return;
            }
        }
        getStudentModal().hide();
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            var btn = document.getElementById('studentModalSubmitBtn');
            var txt = document.getElementById('studentModalSubmitText');
            var ico = document.getElementById('studentModalSubmitIcon');
            btn.disabled = true;
            ico.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            txt.textContent = 'Menyimpan...';
        });
    }

    window.openStudentModal = function (mode, data) {
        data = data || {};
        formDirty = false;
        form.classList.remove('was-validated');

        var title = document.getElementById('studentModalTitle');
        var icon = document.getElementById('studentModalIcon');
        var submitTxt = document.getElementById('studentModalSubmitText');
        var submitIco = document.getElementById('studentModalSubmitIcon');
        var submitBtn = document.getElementById('studentModalSubmitBtn');

        submitBtn.disabled = false;
        submitIco.innerHTML = '<i class="bi bi-check2-circle"></i>';

        if (mode === 'tambah') {
            title.textContent = 'Tambah Mahasiswa Baru';
            icon.className = 'bi bi-person-plus text-primary me-1';
            submitTxt.textContent = 'Simpan Data Mahasiswa';
            form.action = '<?= url('admin/students') ?>';

            document.getElementById('student_modal_edit_id').value = '';
            document.getElementById('student_nim').value = '';
            document.getElementById('student_full_name').value = '';
            document.getElementById('student_faculty').value = 'Teknik & Informatika';
            document.getElementById('student_study_program').value = 'Teknik Informatika';
            document.getElementById('student_academic_year').value = '2024/2025';
            document.getElementById('student_current_semester').value = 1;
            document.getElementById('student_class_group').value = 'TI-01A';
            document.getElementById('student_email').value = '';
            document.getElementById('student_phone').value = '';
            document.getElementById('student_status').value = 'active';

        } else if (mode === 'edit') {
            title.textContent = 'Edit Data Mahasiswa';
            icon.className = 'bi bi-pencil-square text-warning me-1';
            submitTxt.textContent = 'Simpan Perubahan';
            form.action = '<?= url('admin/students/update') ?>?id=' + data.id;

            document.getElementById('student_modal_edit_id').value = data.id || '';
            document.getElementById('student_nim').value = data.nim || '';
            document.getElementById('student_full_name').value = data.full_name || '';
            document.getElementById('student_faculty').value = data.faculty || 'Teknik & Informatika';
            document.getElementById('student_study_program').value = data.study_program || '';
            document.getElementById('student_academic_year').value = data.academic_year || '';
            document.getElementById('student_current_semester').value = data.current_semester || 1;
            document.getElementById('student_class_group').value = data.class_group || '';
            document.getElementById('student_email').value = data.email || '';
            document.getElementById('student_phone').value = data.phone || '';
            document.getElementById('student_status').value = data.status || 'active';
        }

        getStudentModal().show();

        document.getElementById('studentModal').addEventListener('shown.bs.modal', function focusFirst() {
            document.getElementById('student_nim').focus();
            document.getElementById('studentModal').removeEventListener('shown.bs.modal', focusFirst);
        });
    };

    <?php if (!empty($_SESSION['old_input']) && !empty($_SESSION['modal_mode'])): ?>
    (function () {
        var mode = <?= json_encode($_SESSION['modal_mode']) ?>;
        var oldData = {
            id:               <?= json_encode($_SESSION['old_input']['_edit_id'] ?? 0) ?>,
            nim:              <?= json_encode($_SESSION['old_input']['nim'] ?? '') ?>,
            full_name:        <?= json_encode($_SESSION['old_input']['full_name'] ?? '') ?>,
            faculty:          <?= json_encode($_SESSION['old_input']['faculty'] ?? 'Teknik & Informatika') ?>,
            study_program:    <?= json_encode($_SESSION['old_input']['study_program'] ?? '') ?>,
            academic_year:    <?= json_encode($_SESSION['old_input']['academic_year'] ?? '') ?>,
            current_semester: <?= json_encode($_SESSION['old_input']['current_semester'] ?? 1) ?>,
            class_group:      <?= json_encode($_SESSION['old_input']['class_group'] ?? '') ?>,
            email:            <?= json_encode($_SESSION['old_input']['email'] ?? '') ?>,
            phone:            <?= json_encode($_SESSION['old_input']['phone'] ?? '') ?>,
            status:           <?= json_encode($_SESSION['old_input']['status'] ?? 'active') ?>
        };
        window.addEventListener('DOMContentLoaded', function () {
            openStudentModal(mode, oldData);
        });
    })();
    <?php unset($_SESSION['modal_mode']); ?>
    <?php endif; ?>
})();
</script>
