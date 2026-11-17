<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tambah Pengguna Baru</h4>
        <p class="text-secondary small mb-0">Daftarkan akun pengguna sistem dan tentukan peran (Role).</p>
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
        <form action="<?= url('admin/users') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name'] ?? '') ?>" placeholder="Contoh: Budi Prasetyo" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Username Login <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control font-monospace" value="<?= e($old['username'] ?? '') ?>" placeholder="Contoh: budipras" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Email Kampus <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>" placeholder="Contoh: budi@campus.ac.id" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($old['phone'] ?? '') ?>" placeholder="Contoh: 08123456789">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Peran Pengguna (Role) <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <option value="">-- Pilih Peran (Role) --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= ((string)($old['role_id'] ?? '') === (string)$r['id']) ? 'selected' : '' ?>>
                                <?= e($r['display_name']) ?> (<?= e($r['name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Status Akun</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($old['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Kata Sandi Awal <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                <small class="text-muted">Password akan langsung dienkripsi menggunakan algoritma Bcrypt yang aman.</small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Pengguna
                </button>
                <a href="<?= url('admin/users') ?>" class="btn btn-light px-3">Batal</a>
            </div>
        </form>
    </div>
</div>
