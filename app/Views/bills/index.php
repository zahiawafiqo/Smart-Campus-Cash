<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Daftar Tagihan Mahasiswa (Invoicing)</h4>
        <p class="text-secondary small mb-0">Pencatatan, penerbitan, dan pemantauan kewajiban pembayaran SPP dan biaya kuliah.</p>
    </div>
    <a href="<?= url('admin/bills/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-receipt me-1"></i> Terbitkan Tagihan Baru
    </a>
</div>

<?php if (!empty($success)): ?>
    <?php render_component('alert', ['type' => 'success', 'message' => $success, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <?php render_component('alert', ['type' => 'danger', 'message' => $error, 'dismissible' => true, 'class' => 'mb-3']); ?>
<?php endif; ?>

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('admin/bills') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari No Invoice, NIM, Nama, atau Judul..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status Tagihan</option>
                    <option value="UNPAID" <?= $selectedStatus === 'UNPAID' ? 'selected' : '' ?>>Belum Dibayar (UNPAID)</option>
                    <option value="PARTIAL" <?= $selectedStatus === 'PARTIAL' ? 'selected' : '' ?>>Dicicil Sebagian (PARTIAL)</option>
                    <option value="PAID" <?= $selectedStatus === 'PAID' ? 'selected' : '' ?>>Lunas (PAID)</option>
                    <option value="OVERDUE" <?= $selectedStatus === 'OVERDUE' ? 'selected' : '' ?>>Lewat Jatuh Tempo (OVERDUE)</option>
                    <option value="CANCELLED" <?= $selectedStatus === 'CANCELLED' ? 'selected' : '' ?>>Dibatalkan (CANCELLED)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="period" class="form-select form-select-sm">
                    <option value="">Semua Periode</option>
                    <?php foreach ($periodList as $per): ?>
                        <option value="<?= e($per) ?>" <?= $selectedPeriod === $per ? 'selected' : '' ?>><?= e($per) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="<?= url('admin/bills') ?>" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Bills -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Invoice &amp; Mahasiswa</th>
                    <th>Judul &amp; Periode</th>
                    <th>Total Tagihan</th>
                    <th>Sisa Tunggakan</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($bills)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada tagihan yang cocok dengan kriteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($bills as $b): ?>
                        <tr>
                            <td>
                                <a href="<?= url('admin/bills/detail?id=' . $b['id']) ?>" class="font-monospace fw-bold text-primary text-decoration-none d-block">
                                    <?= e($b['invoice_number']) ?>
                                </a>
                                <span class="fw-medium text-dark"><?= e($b['student_name']) ?></span>
                                <small class="text-muted font-monospace d-block">NIM: <?= e($b['nim']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($b['title']) ?></div>
                                <span class="badge bg-light text-dark border"><?= e($b['category_name']) ?></span>
                                <small class="text-muted d-block"><?= e($b['period']) ?></small>
                            </td>
                            <td class="fw-semibold text-dark">
                                Rp <?= number_format((float)$b['amount'], 0, ',', '.') ?>
                            </td>
                            <td>
                                <?php if ((float)$b['remaining_amount'] > 0): ?>
                                    <span class="fw-bold text-danger">Rp <?= number_format((float)$b['remaining_amount'], 0, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="text-success fw-bold">Rp 0 (Lunas)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-muted"><?= date('d M Y', strtotime($b['due_date'])) ?></div>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match($b['status']) {
                                    'PAID' => 'bg-success-subtle text-success',
                                    'PARTIAL' => 'bg-warning-subtle text-warning',
                                    'OVERDUE' => 'bg-danger-subtle text-danger',
                                    'CANCELLED' => 'bg-secondary-subtle text-secondary',
                                    default => 'bg-danger-subtle text-danger'
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= e($b['status']) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('admin/bills/detail?id=' . $b['id']) ?>" class="btn btn-outline-secondary" title="Lihat Invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!in_array($b['status'], ['PAID', 'CANCELLED'])): ?>
                                        <a href="<?= url('admin/bills/edit?id=' . $b['id']) ?>" class="btn btn-outline-secondary" title="Edit Tagihan">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ((float)$b['paid_amount'] == 0): ?>
                                            <button type="button" class="btn btn-outline-danger" title="Batalkan Tagihan" onclick="openCancelModal(<?= (int)$b['id'] ?>, '<?= e($b['invoice_number']) ?>')">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top">
            <?php
            render_component('pagination', [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'baseUrl' => url('admin/bills?search=' . urlencode($search) . '&status=' . urlencode($selectedStatus) . '&period=' . urlencode($selectedPeriod)),
                'totalItems' => $total,
                'perPage' => $perPage
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Batalkan Tagihan -->
<div class="modal fade" id="cancelBillModal" tabindex="-1" aria-labelledby="cancelBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-danger" id="cancelBillModalLabel"><i class="bi bi-exclamation-triangle me-1"></i> Batalkan Tagihan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelBillForm" method="POST" action="">
                <?= csrf_field() ?>
                <div class="modal-body py-3">
                    <p class="small text-muted mb-2">
                        Anda akan membatalkan tagihan <strong id="cancelTargetInv" class="text-dark"></strong>. Status tagihan akan berubah menjadi <strong>CANCELLED</strong>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="Contoh: Kesalahan nominal / mahasiswa mengajukan beasiswa" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-sm btn-danger px-3">Konfirmasi Pembatalan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCancelModal(id, inv) {
    document.getElementById('cancelTargetInv').innerText = inv;
    document.getElementById('cancelBillForm').action = '<?= url('admin/bills/cancel') ?>?id=' + id;
    new bootstrap.Modal(document.getElementById('cancelBillModal')).show();
}
</script>
