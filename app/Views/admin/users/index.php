<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Manajemen Pengguna (User Management)</h4>
        <p class="text-secondary small mb-0">Kelola akun multi-role: Admin, Mahasiswa, Kasir, dan Bagian Keuangan.</p>
    </div>
    <button type="button" id="btnTambahUser" class="btn btn-primary btn-sm" onclick="openUserModal('tambah')">
        <i class="bi bi-person-plus me-1"></i> Tambah Pengguna Baru
    </button>
</div>

<?php if (!empty($success)): ?>
    <?php render_component('alert', ['type' => 'success', 'message' => $success, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('admin/users') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama, username, atau email..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role_id" class="form-select form-select-sm">
                    <option value="">Semua Peran (Role)</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= ((string)$selectedRole === (string)$r['id']) ? 'selected' : '' ?>><?= e($r['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Users -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Nama &amp; Username</th>
                    <th>Email &amp; Kontak</th>
                    <th>Role / Peran</th>
                    <th>Status</th>
                    <th>Waktu Dibuat</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada pengguna yang cocok dengan kriteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($u['full_name']) ?></div>
                                <span class="text-muted font-monospace">@<?= e($u['username']) ?></span>
                            </td>
                            <td>
                                <div><?= e($u['email']) ?></div>
                                <small class="text-muted"><?= e($u['phone'] ?? '-') ?></small>
                            </td>
                            <td>
                                <?php
                                $roleBadge = match($u['role_name']) {
                                    'admin' => 'bg-danger-subtle text-danger',
                                    'mahasiswa' => 'bg-info-subtle text-info',
                                    'kasir' => 'bg-warning-subtle text-warning',
                                    'keuangan' => 'bg-success-subtle text-success',
                                    default => 'bg-secondary-subtle text-secondary'
                                };
                                ?>
                                <span class="badge <?= $roleBadge ?>"><?= e($u['role_display']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" title="Edit Profil & Role"
                                            onclick="openUserModal('edit', {
                                                id: <?= (int)$u['id'] ?>,
                                                full_name: <?= json_encode($u['full_name']) ?>,
                                                username: <?= json_encode($u['username']) ?>,
                                                email: <?= json_encode($u['email']) ?>,
                                                phone: <?= json_encode($u['phone'] ?? '') ?>,
                                                role_id: <?= (int)$u['role_id'] ?>,
                                                status: <?= json_encode($u['status']) ?>
                                            })">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" title="Reset Password" onclick="openResetPasswordModal(<?= (int)$u['id'] ?>, '<?= e($u['username']) ?>')">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <?php if ($u['id'] !== $currentUserId): ?>
                                        <form action="<?= url('admin/users/toggle-status?id=' . $u['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Ubah status aktif akun ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-<?= $u['status'] === 'active' ? 'warning' : 'success' ?>" title="<?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        </form>
                                        <form action="<?= url('admin/users/delete?id=' . $u['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus user ini secara permanen?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus User">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
                'baseUrl' => url('admin/users?search=' . urlencode($search) . '&role_id=' . urlencode($selectedRole) . '&status=' . urlencode($selectedStatus)),
                'totalItems' => $total,
                'perPage' => $perPage
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="resetPasswordModalLabel"><i class="bi bi-key text-warning me-1"></i> Reset Password Pengguna</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPassForm" method="POST" action="">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <p class="small text-muted mb-3">
                        Mereset password untuk akun <strong id="resetTargetUsername" class="text-dark"></strong>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Simpan Password Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openResetPasswordModal(id, username) {
    document.getElementById('resetTargetUsername').innerText = '@' + username;
    document.getElementById('resetPassForm').action = '<?= url('admin/users/reset-password') ?>?id=' + id;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
</script>

<!-- ================================================================
     MODAL: Tambah / Edit Pengguna
     Pola Bootstrap 5 modal konsisten dengan master kategori & resetPasswordModal.
     ================================================================ -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="userModalLabel">
                    <i class="bi bi-person-plus text-primary me-1" id="userModalIcon"></i>
                    <span id="userModalTitle">Tambah Pengguna Baru</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup" id="userModalCloseBtn"></button>
            </div>

            <form id="userModalForm" method="POST" action="<?= url('admin/users') ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_from_modal" value="1">
                <input type="hidden" name="_edit_id" id="user_modal_edit_id" value="">

                <div class="modal-body py-3">
                    <?php if (!empty($error) && !empty($_SESSION['modal_mode'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 small py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="user_full_name" class="form-label small fw-semibold">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="user_full_name" name="full_name" class="form-control"
                                   placeholder="Contoh: Budi Prasetyo" required
                                   value="<?= e($_SESSION['old_input']['full_name'] ?? '') ?>">
                            <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="user_username" class="form-label small fw-semibold">
                                Username Login <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="user_username" name="username" class="form-control font-monospace"
                                   placeholder="Contoh: budipras" required minlength="3"
                                   value="<?= e($_SESSION['old_input']['username'] ?? '') ?>">
                            <div class="invalid-feedback">Username minimal 3 karakter.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="user_email" class="form-label small fw-semibold">
                                Email Kampus <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="user_email" name="email" class="form-control"
                                   placeholder="Contoh: budi@campus.ac.id" required
                                   value="<?= e($_SESSION['old_input']['email'] ?? '') ?>">
                            <div class="invalid-feedback">Format email tidak valid.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="user_phone" class="form-label small fw-semibold">
                                Nomor Telepon / WhatsApp
                            </label>
                            <input type="text" id="user_phone" name="phone" class="form-control"
                                   placeholder="Contoh: 08123456789"
                                   value="<?= e($_SESSION['old_input']['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="user_role_id" class="form-label small fw-semibold">
                                Peran Pengguna (Role) <span class="text-danger">*</span>
                            </label>
                            <select id="user_role_id" name="role_id" class="form-select" required>
                                <option value="">-- Pilih Peran (Role) --</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= ((string)($_SESSION['old_input']['role_id'] ?? '') === (string)$r['id']) ? 'selected' : '' ?>>
                                        <?= e($r['display_name']) ?> (<?= e($r['name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Silakan pilih peran pengguna.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="user_status" class="form-label small fw-semibold">Status Akun</label>
                            <select id="user_status" name="status" class="form-select">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div id="userModalPasswordWrapper" class="mb-2">
                        <label for="user_password" class="form-label small fw-semibold">
                            Kata Sandi Awal <span class="text-danger">*</span>
                        </label>
                        <input type="password" id="user_password" name="password" class="form-control"
                               placeholder="Minimal 6 karakter" minlength="6">
                        <div class="invalid-feedback">Kata sandi minimal 6 karakter.</div>
                        <small class="text-muted">Password akan langsung dienkripsi menggunakan algoritma Bcrypt yang aman.</small>
                    </div>
                </div>

                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" id="userModalCancelBtn">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3" id="userModalSubmitBtn">
                        <span id="userModalSubmitIcon" class="me-1"><i class="bi bi-check2-circle"></i></span>
                        <span id="userModalSubmitText">Simpan Pengguna</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    let userModalInstance = null;
    let formDirty = false;
    const form = document.getElementById('userModalForm');

    function getUserModal() {
        if (!userModalInstance) {
            userModalInstance = new bootstrap.Modal(document.getElementById('userModal'), {
                backdrop: 'static',
                keyboard: false
            });
        }
        return userModalInstance;
    }

    if (form) {
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.addEventListener('input', function () { formDirty = true; });
            el.addEventListener('change', function () { formDirty = true; });
        });
    }

    document.getElementById('userModal').addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            tryCloseUserModal();
        }
    });

    document.getElementById('userModalCloseBtn').addEventListener('click', tryCloseUserModal);
    document.getElementById('userModalCancelBtn').addEventListener('click', tryCloseUserModal);

    function tryCloseUserModal() {
        if (formDirty) {
            if (!confirm('Data yang sudah Anda isi belum disimpan. Tutup dan buang perubahan?')) {
                return;
            }
        }
        getUserModal().hide();
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            var btn = document.getElementById('userModalSubmitBtn');
            var txt = document.getElementById('userModalSubmitText');
            var ico = document.getElementById('userModalSubmitIcon');
            btn.disabled = true;
            ico.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            txt.textContent = 'Menyimpan...';
        });
    }

    window.openUserModal = function (mode, data) {
        data = data || {};
        formDirty = false;
        form.classList.remove('was-validated');

        var title = document.getElementById('userModalTitle');
        var icon = document.getElementById('userModalIcon');
        var submitTxt = document.getElementById('userModalSubmitText');
        var submitIco = document.getElementById('userModalSubmitIcon');
        var submitBtn = document.getElementById('userModalSubmitBtn');
        var pwdWrapper = document.getElementById('userModalPasswordWrapper');
        var pwdInput = document.getElementById('user_password');

        submitBtn.disabled = false;
        submitIco.innerHTML = '<i class="bi bi-check2-circle"></i>';

        if (mode === 'tambah') {
            title.textContent = 'Tambah Pengguna Baru';
            icon.className = 'bi bi-person-plus text-primary me-1';
            submitTxt.textContent = 'Simpan Pengguna';
            form.action = '<?= url('admin/users') ?>';

            pwdWrapper.style.display = '';
            pwdInput.required = true;
            pwdInput.value = '';

            document.getElementById('user_modal_edit_id').value = '';
            document.getElementById('user_full_name').value = '';
            document.getElementById('user_username').value = '';
            document.getElementById('user_email').value = '';
            document.getElementById('user_phone').value = '';
            document.getElementById('user_role_id').value = '';
            document.getElementById('user_status').value = 'active';

        } else if (mode === 'edit') {
            title.textContent = 'Edit Data Pengguna';
            icon.className = 'bi bi-pencil-square text-warning me-1';
            submitTxt.textContent = 'Simpan Perubahan';
            form.action = '<?= url('admin/users/update') ?>?id=' + data.id;

            pwdWrapper.style.display = 'none';
            pwdInput.required = false;
            pwdInput.value = '';

            document.getElementById('user_modal_edit_id').value = data.id || '';
            document.getElementById('user_full_name').value = data.full_name || '';
            document.getElementById('user_username').value = data.username || '';
            document.getElementById('user_email').value = data.email || '';
            document.getElementById('user_phone').value = data.phone || '';
            document.getElementById('user_role_id').value = data.role_id || '';
            document.getElementById('user_status').value = data.status || 'active';
        }

        getUserModal().show();

        document.getElementById('userModal').addEventListener('shown.bs.modal', function focusFirst() {
            document.getElementById('user_full_name').focus();
            document.getElementById('userModal').removeEventListener('shown.bs.modal', focusFirst);
        });
    };

    <?php if (!empty($_SESSION['old_input']) && !empty($_SESSION['modal_mode'])): ?>
    (function () {
        var mode = <?= json_encode($_SESSION['modal_mode']) ?>;
        var oldData = {
            id:        <?= json_encode($_SESSION['old_input']['_edit_id'] ?? 0) ?>,
            full_name: <?= json_encode($_SESSION['old_input']['full_name'] ?? '') ?>,
            username:  <?= json_encode($_SESSION['old_input']['username'] ?? '') ?>,
            email:     <?= json_encode($_SESSION['old_input']['email'] ?? '') ?>,
            phone:     <?= json_encode($_SESSION['old_input']['phone'] ?? '') ?>,
            role_id:   <?= json_encode($_SESSION['old_input']['role_id'] ?? '') ?>,
            status:    <?= json_encode($_SESSION['old_input']['status'] ?? 'active') ?>
        };
        window.addEventListener('DOMContentLoaded', function () {
            openUserModal(mode, oldData);
        });
    })();
    <?php unset($_SESSION['modal_mode']); ?>
    <?php endif; ?>
})();
</script>
