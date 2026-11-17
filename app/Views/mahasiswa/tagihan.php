<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Tagihan Kuliah Saya</h4>
        <p class="text-secondary small mb-0">Daftar seluruh kewajiban pembayaran SPP, registrasi, dan biaya perkuliahan Anda.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('mahasiswa/riwayat') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clock-history me-1"></i> Riwayat Pembayaran
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <?php if (!empty($bills)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No. Invoice</th>
                            <th>Kategori / Periode</th>
                            <th>Uraian Tagihan</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-end">Total Tagihan</th>
                            <th class="text-end">Telah Dibayar</th>
                            <th class="text-end">Sisa Tagihan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $b): ?>
                            <?php
                            $badge = match ($b['status']) {
                                'PAID' => 'success',
                                'PARTIAL' => 'warning text-dark',
                                'OVERDUE' => 'danger',
                                'CANCELLED' => 'secondary',
                                default => 'danger'
                            };
                            ?>
                            <tr>
                                <td class="ps-4 font-monospace fw-bold text-dark"><?= e($b['invoice_number']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($b['category_name']) ?></span>
                                    <div class="small text-muted"><?= e($b['period']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($b['title']) ?></div>
                                    <?php if (!empty($b['notes'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 250px;"><?= e($b['notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small <?= (strtotime($b['due_date']) < time() && $b['status'] !== 'PAID') ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        <?= date('d M Y', strtotime($b['due_date'])) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">Rp <?= number_format((float)$b['amount'], 0, ',', '.') ?></td>
                                <td class="text-end text-success fw-semibold">Rp <?= number_format((float)$b['paid_amount'], 0, ',', '.') ?></td>
                                <td class="text-end text-danger fw-bold">Rp <?= number_format((float)$b['remaining_amount'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $badge ?> px-2.5 py-1.5"><?= e($b['status']) ?></span>
                                </td>
                                <td class="text-center pe-4">
                                    <a href="<?= url('mahasiswa/tagihan/detail?id=' . (int)$b['id']) ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-receipt me-1"></i> Rincian
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-patch-check fs-1 text-success d-block mb-2"></i>
                <h6 class="fw-bold">Tidak Ada Tagihan</h6>
                <p class="small mb-0">Saat ini Anda tidak memiliki catatan tagihan aktif.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
