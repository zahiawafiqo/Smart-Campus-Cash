<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Terbitkan Tagihan Mahasiswa</h4>
        <p class="text-secondary small mb-0">Input rincian biaya kuliah, SPP, atau kewajiban pembayaran lainnya.</p>
    </div>
    <a href="<?= url('admin/bills') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 max-w-3xl">
    <div class="card-body p-4">
        <form action="<?= url('admin/bills') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Nomor Invoice <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_number" class="form-control font-monospace" value="<?= e($old['invoice_number'] ?? $suggestedInv) ?>" required>
                    <small class="text-muted">Dibuat otomatis secara unik atau dapat disesuaikan.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Mahasiswa Penerima Tagihan <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= ((string)($old['student_id'] ?? '') === (string)$s['id']) ? 'selected' : '' ?>>
                                <?= e($s['nim']) ?> - <?= e($s['full_name']) ?> (<?= e($s['study_program']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Kategori Jenis Tagihan <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= ((string)($old['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?> (<?= e($cat['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Judul Tagihan <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= e($old['title'] ?? '') ?>" placeholder="Contoh: SPP Semester Gasal 2026/2027" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Periode Akademik <span class="text-danger">*</span></label>
                    <input type="text" name="period" class="form-control" value="<?= e($old['period'] ?? '2026/2027 Gasal') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Nominal Tagihan (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control fw-bold text-primary fs-6" value="<?= e($old['amount'] ?? '') ?>" placeholder="Contoh: 3500000" min="1" step="any" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Jatuh Tempo <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="<?= e($old['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Catatan / Petunjuk Pembayaran</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Informasi rekening tujuan atau ketentuan denda keterlambatan..."><?= e($old['notes'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2-circle me-1"></i> Simpan &amp; Terbitkan Tagihan
                </button>
                <a href="<?= url('admin/bills') ?>" class="btn btn-light px-3">Batal</a>
            </div>
        </form>
    </div>
</div>
