<?php
// View: keuangan/transaksi/create.php
$isIncome = ($type ?? 'income') === 'income';
$typeLabel = $isIncome ? 'Kas Masuk' : 'Kas Keluar';
$typeColor = $isIncome ? 'success' : 'danger';
$typeIcon  = $isIncome ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill';
?>
<div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="<?= $typeIcon ?> text-<?= $typeColor ?> me-2"></i>Catat <?= e($typeLabel) ?>
        </h4>
        <p class="text-secondary small mb-0">Pencatatan mutasi buku kas <?= $isIncome ? 'penerimaan' : 'pengeluaran' ?> institusi.</p>
    </div>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show small"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center gap-2">
        <span class="badge bg-<?= $typeColor ?>-subtle text-<?= $typeColor ?> border fs-6 px-3 py-2">
            <i class="<?= $typeIcon ?> me-1"></i><?= e($typeLabel) ?>
        </span>
    </div>
    <div class="card-body p-4">
        <form action="<?= url('keuangan/transaksi/store') ?>" method="POST" id="transactionForm" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= e($type ?? 'income') ?>">

            <div class="row g-3">
                <!-- No. Transaksi -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">No. Transaksi <span class="text-danger">*</span></label>
                    <input type="text" name="transaction_number" class="form-control font-monospace"
                           value="<?= e($old['transaction_number'] ?? $suggestedTnum ?? '') ?>"
                           required maxlength="50" placeholder="e.g. KM/2026/09/0001">
                    <div class="form-text">Nomor unik otomatis tersugesti, dapat diubah.</div>
                </div>

                <!-- Kategori -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($old['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nominal -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Nominal (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="amount" class="form-control fw-bold text-<?= $typeColor ?>"
                               min="1" step="1" required placeholder="0"
                               value="<?= e($old['amount'] ?? '') ?>">
                    </div>
                </div>

                <!-- Tanggal & Waktu -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Tanggal & Waktu <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="transaction_date" class="form-control"
                           value="<?= e($old['transaction_date'] ?? date('Y-m-d\TH:i')) ?>" required>
                </div>

                <!-- Deskripsi -->
                <div class="col-12">
                    <label class="form-label fw-semibold small">Deskripsi <span class="text-danger">*</span></label>
                    <input type="text" name="description" class="form-control"
                           maxlength="255" required
                           placeholder="Deskripsi singkat transaksi..."
                           value="<?= e($old['description'] ?? '') ?>">
                </div>

                <!-- Metode -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Metode Pembayaran</label>
                    <select name="payment_method" class="form-select">
                        <?php
                        $methods = ['cash' => 'Tunai (Cash)', 'qris' => 'QRIS', 'bank_transfer' => 'Transfer Bank', 'edc' => 'Kartu Debit / EDC'];
                        foreach ($methods as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= (($old['payment_method'] ?? 'cash') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- No. Referensi -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">No. Referensi (Opsional)</label>
                    <input type="text" name="reference_number" class="form-control font-monospace"
                           maxlength="100" placeholder="No. referensi bukti..."
                           value="<?= e($old['reference_number'] ?? '') ?>">
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url('keuangan/transaksi') ?>" class="btn btn-outline-secondary rounded-2">Batal</a>
                <button type="submit" class="btn btn-<?= $typeColor ?> px-4 rounded-2">
                    <i class="bi bi-save me-1"></i> Simpan Transaksi
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
