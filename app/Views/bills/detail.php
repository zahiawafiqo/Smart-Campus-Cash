<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold text-dark mb-0">Rincian Invoice <?= e($bill['invoice_number']) ?></h4>
            <?php
            $badgeColor = match ($bill['status']) {
                'PAID' => 'success',
                'PARTIAL' => 'warning text-dark',
                'OVERDUE' => 'danger',
                'CANCELLED' => 'secondary',
                default => 'danger'
            };
            ?>
            <span class="badge bg-<?= $badgeColor ?> px-3 py-2 fs-6"><?= e($bill['status']) ?></span>
        </div>
        <p class="text-secondary small mb-0 mt-1">Diterbitkan pada <?= date('d M Y H:i', strtotime($bill['created_at'])) ?></p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Cetak Invoice
        </button>
        <?php if (!in_array($bill['status'], ['PAID', 'CANCELLED'])): ?>
            <a href="<?= url('admin/bills/edit?id=' . (int)$bill['id']) ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit Tagihan
            </a>
            <?php if ((float)$bill['paid_amount'] == 0): ?>
                <form action="<?= url('admin/bills/cancel?id=' . (int)$bill['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan tagihan ini?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="reason" value="Dibatalkan via rincian tagihan admin">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Batalkan
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        <a href="<?= url('admin/bills') ?>" class="btn btn-light border btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Invoice Main Card -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="text-uppercase text-muted fw-semibold small">Informasi Mahasiswa</h6>
                        <h5 class="fw-bold text-dark mb-1"><?= e($bill['student_name']) ?></h5>
                        <p class="text-secondary mb-1"><strong>NIM:</strong> <?= e($bill['nim']) ?></p>
                        <p class="text-secondary mb-1"><strong>Prodi:</strong> <?= e($bill['study_program']) ?></p>
                        <p class="text-secondary mb-0"><strong>Fakultas:</strong> <?= e($bill['faculty'] ?? '-') ?></p>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <h6 class="text-uppercase text-muted fw-semibold small">Detail Tagihan</h6>
                        <p class="text-secondary mb-1"><strong>No Invoice:</strong> <span class="font-monospace fw-bold text-dark"><?= e($bill['invoice_number']) ?></span></p>
                        <p class="text-secondary mb-1"><strong>Periode:</strong> <?= e($bill['period']) ?></p>
                        <p class="text-secondary mb-1"><strong>Kategori:</strong> <?= e($bill['category_name']) ?></p>
                        <p class="text-secondary mb-0"><strong>Jatuh Tempo:</strong> <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><?= date('d M Y', strtotime($bill['due_date'])) ?></span></p>
                    </div>
                </div>

                <hr class="my-4">

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Deskripsi Tagihan</th>
                                <th class="text-end" style="width: 200px;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($bill['title']) ?></div>
                                    <div class="small text-muted"><?= e($bill['notes'] ?? $bill['description'] ?? 'Biaya kewajiban akademik mahasiswa') ?></div>
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
                                <td class="text-end fw-bold fs-6">Sisa Pembayaran:</td>
                                <td class="text-end fw-bold fs-6 text-danger">Rp <?= number_format((float)$bill['remaining_amount'], 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payments History Table -->
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-1"></i> Riwayat Pembayaran untuk Invoice Ini</h6>
                <?php if (!empty($bill['payments'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>No Pembayaran</th>
                                    <th>Waktu</th>
                                    <th>Metode</th>
                                    <th>Kasir</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bill['payments'] as $p): ?>
                                    <tr>
                                        <td class="font-monospace fw-semibold"><?= e($p['payment_number']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                                        <td><span class="badge bg-secondary"><?= e($p['payment_method']) ?></span></td>
                                        <td><?= e($p['cashier_name'] ?? 'Kasir') ?></td>
                                        <td class="text-end fw-bold text-success">Rp <?= number_format((float)$p['amount'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($p['status'] === 'completed') ? 'success' : 'secondary' ?>">
                                                <?= e($p['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-light rounded text-center text-muted small">
                        Belum ada transaksi pembayaran yang tercatat untuk tagihan ini.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Status Summary -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-1"></i> Ringkasan Status</h6>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Status Pembayaran</span>
                    <span class="fw-bold text-<?= $badgeColor ?>"><?= e($bill['status']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Jatuh Tempo</span>
                    <span class="fw-semibold text-dark"><?= date('d F Y', strtotime($bill['due_date'])) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Total Kewajiban</span>
                    <span class="fw-bold text-dark">Rp <?= number_format((float)$bill['amount'], 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Sisa Tagihan</span>
                    <span class="fw-bold text-danger">Rp <?= number_format((float)$bill['remaining_amount'], 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Persentase Lunas</span>
                    <?php 
                    $pct = ($bill['amount'] > 0) ? min(100, round(($bill['paid_amount'] / $bill['amount']) * 100)) : 0;
                    ?>
                    <span class="fw-bold text-primary"><?= $pct ?>%</span>
                </div>
                <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
</div>
