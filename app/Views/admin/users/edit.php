<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Data Pengguna</h4>
        <p class="text-secondary small mb-0">Perbarui informasi profil dan penugasan peran (Role Assignment).</p>
    </div>
    <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar User
    </a>
</div>

<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 max-w-2xl">
    <div class="card-body p-4">
        <form action="<?= url('admin/users/update?id=' . $user['id']) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Username Login <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control font-monospace" value="<?= e($user['username']) ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email Kampus <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Peran Pengguna (Role Assignment) <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= ((int)$user['role_id'] === (int)$r['id']) ? 'selected' : '' ?>>
                                <?= e($r['display_name']) ?> (<?= e($r['name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Status Akun</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
                <a href="<?= url('admin/users') ?>" class="btn btn-light px-3">Batal</a>
            </div>
        </form>
    </div>
</div>
