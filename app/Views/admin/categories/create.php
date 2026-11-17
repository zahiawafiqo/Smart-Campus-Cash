<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tambah Kategori Transaksi</h4>
        <p class="text-secondary small mb-0">Definisikan pos baru untuk arus kas masuk atau keluar.</p>
    </div>
    <a href="<?= url('admin/categories') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 max-w-2xl">
    <div class="card-body p-4">
        <form action="<?= url('admin/categories') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Kode Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control font-monospace" value="<?= e($old['code'] ?? '') ?>" placeholder="Contoh: KAT-SPP" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($old['name'] ?? '') ?>" placeholder="Contoh: SPP Kuliah Reguler" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Tipe Arus Kas <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="income" <?= ($old['type'] ?? 'income') === 'income' ? 'selected' : '' ?>>Pemasukan (Income)</option>
                        <option value="expense" <?= ($old['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Pengeluaran (Expense)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Status Kategori</label>
                    <select name="is_active" class="form-select">
                        <option value="1" selected>Aktif Digunakan</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Deskripsi / Keterangan Pos</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Penjelasan mengenai pos keuangan ini..."><?= e($old['description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Kategori
                </button>
                <a href="<?= url('admin/categories') ?>" class="btn btn-light px-3">Batal</a>
            </div>
        </form>
    </div>
</div>
