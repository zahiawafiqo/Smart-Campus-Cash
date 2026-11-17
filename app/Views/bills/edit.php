<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Tagihan Mahasiswa</h4>
        <p class="text-secondary small mb-0">Perbarui rincian atau tanggal jatuh tempo untuk tagihan <strong><?= e($bill['invoice_number']) ?></strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/bills/detail?id=' . (int)$bill['id']) ?>" class="btn btn-outline-info btn-sm">
            <i class="bi bi-eye me-1"></i> Lihat Detail
        </a>
        <a href="<?= url('admin/bills') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 max-w-3xl">
    <div class="card-body p-4">
        <!-- Mahasiswa Info Box -->
        <div class="p-3 bg-light rounded-3 mb-4 border d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="bi bi-person-badge fs-4"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold"><?= e($bill['student_name'] ?? '-') ?> <span class="badge bg-secondary font-monospace ms-1"><?= e($bill['nim'] ?? '-') ?></span></h6>
                <div class="small text-muted"><?= e($bill['study_program'] ?? '-') ?> &bull; <?= e($bill['faculty'] ?? '-') ?> &bull; Status: <span class="badge bg-<?= ($bill['status'] === 'PAID') ? 'success' : (($bill['status'] === 'PARTIAL') ? 'warning' : 'danger') ?>"><?= e($bill['status']) ?></span></div>
            </div>
        </div>

        <form action="<?= url('admin/bills/update?id=' . (int)$bill['id']) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nomor Invoice <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_number" class="form-control font-monospace" value="<?= e($bill['invoice_number']) ?>" required>
                    <small class="text-muted">Harus unik di seluruh sistem.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Kategori Jenis Tagihan <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= ((int)$bill['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?> (<?= e($cat['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Judul Tagihan <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= e($bill['title']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Periode Akademik <span class="text-danger">*</span></label>
                    <input type="text" name="period" class="form-control" value="<?= e($bill['period']) ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nominal Total (Rp) <span class="text-danger">*</span></label>
                    <input type="number" step="1000" min="<?= (float)$bill['paid_amount'] > 0 ? (float)$bill['paid_amount'] : 1 ?>" name="amount" class="form-control fw-bold" value="<?= (float)$bill['amount'] ?>" required>
                    <?php if ((float)$bill['paid_amount'] > 0): ?>
                        <small class="text-warning"><i class="bi bi-info-circle me-1"></i> Sudah terbayar Rp <?= number_format((float)$bill['paid_amount'], 0, ',', '.') ?>. Nominal minimal Rp <?= number_format((float)$bill['paid_amount'], 0, ',', '.') ?>.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="<?= e($bill['due_date']) ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Catatan / Keterangan Tambahan</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($bill['notes'] ?? $bill['description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('admin/bills') ?>" class="btn btn-light px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
