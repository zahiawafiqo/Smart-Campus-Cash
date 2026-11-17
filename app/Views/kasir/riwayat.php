<?php
// View: kasir/riwayat.php
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Pembayaran Loket</h4>
        <p class="text-secondary small mb-0">Seluruh transaksi pembayaran yang telah diproses di loket.</p>
    </div>
    <a href="<?= url('kasir/pembayaran') ?>" class="btn btn-primary btn-sm rounded-2">
        <i class="bi bi-plus-circle me-1"></i> Proses Pembayaran Baru
    </a>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('kasir/riwayat') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="No. Kuitansi / NIM / Nama..." value="<?= e($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= e($selectedDate ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Metode</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="cash" <?= ($selectedMethod ?? '') === 'cash' ? 'selected' : '' ?>>Tunai</option>
                    <option value="qris" <?= ($selectedMethod ?? '') === 'qris' ? 'selected' : '' ?>>QRIS</option>
                    <option value="bank_transfer" <?= ($selectedMethod ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Transfer Bank</option>
                    <option value="edc" <?= ($selectedMethod ?? '') === 'edc' ? 'selected' : '' ?>>EDC</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm me-1">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="<?= url('kasir/riwayat') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Riwayat -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small">Total: <?= number_format($total ?? 0) ?> transaksi</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($payments)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                <p class="mb-0">Tidak ada data pembayaran yang sesuai filter.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No. Kuitansi</th>
                        <th>Mahasiswa</th>
                        <th>Invoice</th>
                        <th>Metode</th>
                        <th class="text-end">Nominal</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="ps-3 font-monospace fw-semibold text-primary"><?= e($p['payment_number']) ?></td>
                        <td>
                            <span class="fw-medium d-block"><?= e($p['student_name']) ?></span>
                            <span class="font-monospace text-muted"><?= e($p['nim']) ?></span>
                        </td>
                        <td class="font-monospace text-secondary"><?= e($p['invoice_number'] ?? '-') ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary border"><?= strtoupper(e($p['payment_method'])) ?></span></td>
                        <td class="text-end fw-bold text-success">Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['payment_date'])) ?></td>
                        <td>
                            <?php
                            $stClass = match($p['status']) {
                                'completed' => 'bg-success',
                                'refunded'  => 'bg-danger',
                                default     => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $stClass ?>"><?= e($p['status']) ?></span>
                        </td>
                        <td class="text-center pe-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="<?= url('kasir/kuitansi?id=' . (int)$p['id']) ?>" class="btn btn-outline-primary btn-sm py-0 px-2" target="_blank" title="Lihat Kuitansi">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <?php if ($p['status'] === 'completed' && auth_role() !== 'kasir' || auth_role() === 'admin'): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2"
                                    onclick="openVoidModal(<?= (int)$p['id'] ?>, '<?= e($p['payment_number']) ?>')"
                                    title="Void/Batalkan">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($p['status'] === 'completed' && auth_role() === 'kasir'): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2"
                                    onclick="openVoidModal(<?= (int)$p['id'] ?>, '<?= e($p['payment_number']) ?>')"
                                    title="Void/Batalkan">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if (($totalPages ?? 1) > 1): ?>
    <div class="card-footer bg-white border-top py-2">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php for ($i = 1; $i <= ($totalPages ?? 1); $i++): ?>
                    <li class="page-item <?= $i === ($page ?? 1) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('kasir/riwayat?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Void -->
<div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-1"></i>Batalkan Transaksi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('kasir/void') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="voidPaymentId">
                <div class="modal-body py-3">
                    <p class="small text-muted mb-2">Membatalkan kuitansi: <strong id="voidPaymentNo">-</strong></p>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2" required placeholder="Masukkan alasan void..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Konfirmasi Void</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openVoidModal(id, paymentNo) {
    document.getElementById('voidPaymentId').value = id;
    document.getElementById('voidPaymentNo').textContent = paymentNo;
    new bootstrap.Modal(document.getElementById('voidModal')).show();
}
</script>
