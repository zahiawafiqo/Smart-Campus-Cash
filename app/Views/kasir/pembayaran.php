<?php
// View: kasir/pembayaran.php
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-cash-coin text-success me-2"></i>Loket Pembayaran Mahasiswa</h4>
        <p class="text-secondary small mb-0">Cari mahasiswa berdasarkan NIM atau nama, lalu proses pembayaran tagihan aktif.</p>
    </div>
    <a href="<?= url('kasir/riwayat') ?>" class="btn btn-outline-secondary btn-sm rounded-2">
        <i class="bi bi-clock-history me-1"></i> Riwayat Pembayaran
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Pencarian Mahasiswa -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-search me-1 text-primary"></i> Cari Mahasiswa</h6>
        <form action="<?= url('kasir/pembayaran') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control" placeholder="Ketik NIM atau Nama Mahasiswa..." value="<?= e($search ?? '') ?>" autofocus>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
            </div>
        </form>

        <?php if (!empty($search) && !empty($searchResults)): ?>
        <div class="mt-4 border-top pt-3">
            <h6 class="small fw-bold text-muted mb-3">Ditemukan <?= count($searchResults) ?> mahasiswa</h6>
            <div class="row g-2">
                <?php foreach ($searchResults as $s): ?>
                <div class="col-md-6">
                    <a href="<?= url('kasir/pembayaran?search=' . urlencode($search ?? '') . '&student_id=' . (int)$s['id']) ?>"
                       class="d-block p-3 rounded-3 border <?= ((int)($selectedStudent['id'] ?? 0) === (int)$s['id']) ? 'border-primary bg-primary-subtle' : 'bg-light' ?> text-decoration-none">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="fw-bold text-dark d-block"><?= e($s['full_name']) ?></span>
                                <span class="font-monospace small text-muted">NIM: <?= e($s['nim']) ?></span>
                                <span class="d-block text-secondary small"><?= e($s['study_program']) ?></span>
                            </div>
                            <div class="text-end">
                                <?php if ((int)$s['unpaid_count'] > 0): ?>
                                    <span class="badge bg-danger"><?= (int)$s['unpaid_count'] ?> Tagihan</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Lunas</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif (!empty($search)): ?>
        <div class="mt-3 text-muted small"><i class="bi bi-info-circle me-1"></i> Tidak ditemukan mahasiswa dengan kata kunci tersebut.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Tagihan Aktif Mahasiswa Terpilih -->
<?php if ($selectedStudent): ?>
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="fw-bold mb-0"><?= e($selectedStudent['full_name']) ?></h6>
            <small class="text-muted font-monospace">NIM: <?= e($selectedStudent['nim']) ?> &bull; <?= e($selectedStudent['study_program']) ?></small>
        </div>
        <span class="badge bg-primary-subtle text-primary"><?= count($activeBills) ?> Tagihan Aktif</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($activeBills)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                <p class="mb-0">Semua tagihan mahasiswa ini telah lunas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($activeBills as $b): ?>
            <div class="p-3 p-md-4 border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold text-dark"><?= e($b['title']) ?></span>
                            <?php
                            $stClass = match($b['status']) {
                                'OVERDUE'  => 'bg-danger',
                                'PARTIAL'  => 'bg-warning text-dark',
                                default    => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $stClass ?>"><?= e($b['status']) ?></span>
                        </div>
                        <small class="text-muted d-block">Invoice: <span class="font-monospace"><?= e($b['invoice_number']) ?></span> &bull; <?= e($b['category_name']) ?></small>
                        <small class="text-muted">Jatuh tempo: <?= date('d F Y', strtotime($b['due_date'])) ?></small>
                        <div class="mt-2">
                            <div class="d-flex gap-3 small">
                                <span>Total: <strong>Rp <?= number_format($b['amount'], 0, ',', '.') ?></strong></span>
                                <span class="text-success">Terbayar: <strong>Rp <?= number_format($b['paid_amount'], 0, ',', '.') ?></strong></span>
                                <span class="text-danger">Sisa: <strong>Rp <?= number_format($b['remaining_amount'], 0, ',', '.') ?></strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button type="button" class="btn btn-success rounded-2"
                            onclick="openBayarModal(<?= (int)$b['id'] ?>, '<?= e($b['invoice_number']) ?>', <?= (float)$b['remaining_amount'] ?>, '<?= e($b['title']) ?>')">
                            <i class="bi bi-wallet2 me-1"></i> Proses Bayar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal Proses Pembayaran -->
<div class="modal fade" id="bayarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-cash-coin text-success me-1"></i> Proses Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('kasir/bayar') ?>" method="POST" id="formBayar">
                <?= csrf_field() ?>
                <input type="hidden" name="idempotency_token" id="idempotencyToken">
                <input type="hidden" name="bill_id" id="hiddenBillId">
                <div class="modal-body py-3">
                    <div class="bg-light rounded-3 p-3 mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Mahasiswa:</span>
                            <strong><?= e($selectedStudent['full_name'] ?? '-') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Invoice:</span>
                            <span class="font-monospace text-primary" id="modalInvoice">-</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Tagihan:</span>
                            <span id="modalBillTitle">-</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="modalAmount" class="form-control form-control-lg fw-bold text-success"
                               min="1" step="1" required placeholder="0">
                        <div class="form-text">Sisa tagihan: <strong class="text-danger" id="modalRemaining">-</strong></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Tunai (Cash)</option>
                            <option value="qris">QRIS</option>
                            <option value="bank_transfer">Transfer Bank</option>
                            <option value="edc">Kartu Debit / EDC</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="form-control" maxlength="200" placeholder="Catatan tambahan...">
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="bi bi-check2-circle me-1"></i> Validasi &amp; Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openBayarModal(billId, invoice, remaining, title) {
    document.getElementById('hiddenBillId').value = billId;
    document.getElementById('modalInvoice').textContent = invoice;
    document.getElementById('modalBillTitle').textContent = title;
    document.getElementById('modalAmount').value = remaining;
    document.getElementById('modalAmount').max = remaining;
    document.getElementById('modalRemaining').textContent = 'Rp ' + remaining.toLocaleString('id-ID');
    // Generate idempotency token
    document.getElementById('idempotencyToken').value = Date.now() + '-' + Math.random().toString(36).substr(2,9);
    new bootstrap.Modal(document.getElementById('bayarModal')).show();
}
</script>
