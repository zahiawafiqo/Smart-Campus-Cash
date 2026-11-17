<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold text-dark mb-0">Rincian Tagihan <?= e($bill['invoice_number']) ?></h4>
            <?php
            $badgeColor = match ($bill['status']) {
                'PAID' => 'success',
                'PARTIAL' => 'warning text-dark',
                'OVERDUE' => 'danger',
                'CANCELLED' => 'secondary',
                default => 'danger'
            };
            ?>
            <span class="badge bg-<?= $badgeColor ?> px-3 py-1.5 fs-6"><?= e($bill['status']) ?></span>
        </div>
        <p class="text-secondary small mb-0 mt-1">Diterbitkan untuk Semester <?= e($bill['period']) ?> &bull; Jatuh tempo: <?= date('d M Y', strtotime($bill['due_date'])) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('mahasiswa/tagihan') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Main Invoice Details -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="text-uppercase text-muted fw-semibold small">Identitas Mahasiswa</h6>
                        <h5 class="fw-bold text-dark mb-1"><?= e($student['full_name']) ?></h5>
                        <p class="text-secondary mb-1"><strong>NIM:</strong> <?= e($student['nim']) ?></p>
                        <p class="text-secondary mb-1"><strong>Program Studi:</strong> <?= e($student['study_program']) ?></p>
                        <p class="text-secondary mb-0"><strong>Fakultas:</strong> <?= e($student['faculty'] ?? '-') ?></p>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <h6 class="text-uppercase text-muted fw-semibold small">Detail Tagihan</h6>
                        <p class="text-secondary mb-1"><strong>No Invoice:</strong> <span class="font-monospace fw-bold text-dark"><?= e($bill['invoice_number']) ?></span></p>
                        <p class="text-secondary mb-1"><strong>Kategori:</strong> <?= e($bill['category_name']) ?></p>
                        <p class="text-secondary mb-0"><strong>Jatuh Tempo:</strong> <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><?= date('d M Y', strtotime($bill['due_date'])) ?></span></p>
                    </div>
                </div>

                <hr class="my-4">

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Deskripsi Item Tagihan</th>
                                <th class="text-end" style="width: 200px;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($bill['title']) ?></div>
                                    <div class="small text-muted"><?= e($bill['notes'] ?? $bill['description'] ?? 'Biaya kewajiban perkuliahan') ?></div>
                                </td>
                                <td class="text-end fw-bold fs-6">Rp <?= number_format((float)$bill['amount'], 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-end fw-semibold">Total Tagihan:</td>
                                <td class="text-end fw-bold">Rp <?= number_format((float)$bill['amount'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="table-success">
                                <td class="text-end fw-semibold">Telah Dibayar:</td>
                                <td class="text-end fw-bold text-success">Rp <?= number_format((float)$bill['paid_amount'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="table-warning">
                                <td class="text-end fw-bold fs-6">Sisa Kewajiban:</td>
                                <td class="text-end fw-bold fs-6 text-danger">Rp <?= number_format((float)$bill['remaining_amount'], 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payments History Table -->
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-1"></i> Riwayat Pembayaran Invoice Ini</h6>
                <?php if (!empty($bill['payments'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Kuitansi</th>
                                    <th>Waktu</th>
                                    <th>Metode</th>
                                    <th>Pencatat</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bill['payments'] as $p): ?>
                                    <tr>
                                        <td class="font-monospace fw-semibold text-primary"><?= e($p['payment_number']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['payment_date'] ?? $p['created_at'])) ?></td>
                                        <td><span class="badge bg-light text-dark border text-uppercase"><?= e($p['payment_method']) ?></span></td>
                                        <td><?= e($p['cashier_name'] ?? 'Sistem/Online') ?></td>
                                        <td class="text-end fw-bold text-success">Rp <?= number_format((float)$p['amount'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="bi bi-check-circle me-1"></i><?= e($p['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-light rounded text-center text-muted small">
                        Belum ada riwayat pembayaran yang tercatat untuk invoice ini.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Action & Payment Gateway -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-credit-card-2-front me-1"></i> Pembayaran Online</h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-3 text-center">
                    <span class="text-muted small d-block mb-1">Sisa yang harus dibayar:</span>
                    <h3 class="fw-bold <?= ((float)$bill['remaining_amount'] > 0) ? 'text-danger' : 'text-success' ?> mb-0">
                        Rp <?= number_format((float)$bill['remaining_amount'], 0, ',', '.') ?>
                    </h3>
                </div>

                <?php if ((float)$bill['remaining_amount'] > 0 && !in_array($bill['status'], ['PAID', 'CANCELLED'])): ?>
                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-3">
                        <i class="bi bi-shield-check me-1"></i> Pembayaran instan otomatis via <strong>Midtrans Payment Gateway</strong> (QRIS, VA Bank Transfer, Gopay, dll).
                    </div>

                    <button type="button" id="btn-pay-online" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span>Bayar Sekarang (Online)</span>
                    </button>

                    <div id="payment-loading" class="text-center py-2 d-none">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="small text-muted ms-1">Menyiapkan pembayaran aman...</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success py-3 text-center mb-0 rounded-3">
                        <i class="bi bi-patch-check-fill fs-3 d-block mb-1 text-success"></i>
                        <strong class="d-block">Tagihan Sudah Lunas</strong>
                        <span class="small">Kewajiban pembayaran untuk tagihan ini telah selesai.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Snap JS Client Integration -->
<?php if (!empty($clientKey) && !empty($snapJsUrl)): ?>
    <script src="<?= e($snapJsUrl) ?>" data-client-key="<?= e($clientKey) ?>"></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payBtn = document.getElementById('btn-pay-online');
    const loadingDiv = document.getElementById('payment-loading');

    if (payBtn) {
        payBtn.addEventListener('click', function () {
            payBtn.disabled = true;
            if (loadingDiv) loadingDiv.classList.remove('d-none');

            fetch('<?= url("mahasiswa/tagihan/bayar-online") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'bill_id': '<?= (int)$bill["id"] ?>',
                    'amount': '<?= (float)$bill["remaining_amount"] ?>'
                })
            })
            .then(res => res.json())
            .then(data => {
                payBtn.disabled = false;
                if (loadingDiv) loadingDiv.classList.add('d-none');

                if (!data.success) {
                    alert(data.message || 'Gagal menyiapkan transaksi pembayaran.');
                    return;
                }

                if (window.snap && data.snap_token) {
                    window.snap.pay(data.snap_token, {
                        onSuccess: function (result) {
                            window.location.href = '<?= url("mahasiswa/pembayaran/finish") ?>?order_id=' + (result.order_id || '');
                        },
                        onPending: function (result) {
                            window.location.href = '<?= url("mahasiswa/tagihan") ?>';
                        },
                        onError: function (result) {
                            alert('Pembayaran gagal atau dibatalkan.');
                        },
                        onClose: function () {
                            console.log('Mahasiswa menutup dialog pembayaran.');
                        }
                    });
                } else if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Sesi pembayaran Midtrans berhasil dibuat: ' + data.order_id);
                }
            })
            .catch(err => {
                payBtn.disabled = false;
                if (loadingDiv) loadingDiv.classList.add('d-none');
                console.error(err);
                alert('Terjadi kesalahan jaringan saat menghubungi server.');
            });
        });
    }
});
</script>
