<?php
// View: kasir/sukses.php
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Pembayaran Berhasil</h4>
        <p class="text-secondary small mb-0">Transaksi telah divalidasi dan kuitansi resmi diterbitkan.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= e($success) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4 text-center">
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle rounded-circle" style="width:72px;height:72px;">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-success mb-1">Pembayaran Sukses!</h5>
                <p class="text-muted mb-4">Kuitansi pembayaran telah diterbitkan dan saldo buku kas telah diperbarui.</p>

                <?php if ($payment): ?>
                <div class="bg-light rounded-3 p-3 mb-4 text-start">
                    <div class="row g-2 small">
                        <div class="col-5 text-muted">No. Kuitansi</div>
                        <div class="col-7 fw-semibold font-monospace text-primary"><?= e($payment['payment_number']) ?></div>
                        <div class="col-5 text-muted">Mahasiswa</div>
                        <div class="col-7 fw-medium"><?= e($payment['student_name']) ?></div>
                        <div class="col-5 text-muted">NIM</div>
                        <div class="col-7 font-monospace"><?= e($payment['nim']) ?></div>
                        <div class="col-5 text-muted">Tagihan</div>
                        <div class="col-7"><?= e($payment['bill_title'] ?? '-') ?></div>
                        <div class="col-5 text-muted">Invoice</div>
                        <div class="col-7 font-monospace"><?= e($payment['invoice_number'] ?? '-') ?></div>
                        <div class="col-5 text-muted">Nominal</div>
                        <div class="col-7 fw-bold text-success fs-6">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></div>
                        <div class="col-5 text-muted">Metode</div>
                        <div class="col-7"><span class="badge bg-secondary"><?= strtoupper(e($payment['payment_method'])) ?></span></div>
                        <div class="col-5 text-muted">Waktu</div>
                        <div class="col-7"><?= date('d F Y, H:i', strtotime($payment['payment_date'])) ?> WIB</div>
                        <div class="col-5 text-muted">Status Tagihan</div>
                        <div class="col-7">
                            <?php
                            $stClass = match($payment['bill_status'] ?? 'UNPAID') {
                                'PAID'    => 'bg-success',
                                'PARTIAL' => 'bg-warning text-dark',
                                default   => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $stClass ?>"><?= e($payment['bill_status'] ?? '-') ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="<?= url('kasir/kuitansi?id=' . (int)($payment['id'] ?? 0)) ?>" class="btn btn-primary rounded-2" target="_blank">
                        <i class="bi bi-printer me-1"></i> Cetak Kuitansi
                    </a>
                    <a href="<?= url('kasir/pembayaran') ?>" class="btn btn-success rounded-2">
                        <i class="bi bi-plus-circle me-1"></i> Proses Pembayaran Baru
                    </a>
                    <a href="<?= url('kasir/dashboard') ?>" class="btn btn-outline-secondary rounded-2">
                        <i class="bi bi-house me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
