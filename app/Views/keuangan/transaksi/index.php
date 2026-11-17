<?php
// View: keuangan/transaksi/index.php
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-journal-text text-primary me-2"></i>Buku Kas Umum</h4>
        <p class="text-secondary small mb-0">Seluruh mutasi kas masuk dan kas keluar institusi.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('keuangan/transaksi/create?type=income') ?>" class="btn btn-success btn-sm rounded-2">
            <i class="bi bi-plus-circle me-1"></i> Kas Masuk
        </a>
        <a href="<?= url('keuangan/transaksi/create?type=expense') ?>" class="btn btn-danger btn-sm rounded-2">
            <i class="bi bi-dash-circle me-1"></i> Kas Keluar
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show small"><i class="bi bi-check-circle me-1"></i><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show small"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-success">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Total Pemasukan</p>
                    <h5 class="fw-bold text-success mb-0">Rp <?= number_format($totalIncome, 0, ',', '.') ?></h5>
                </div>
                <div class="p-3 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:46px;height:46px;">
                    <i class="bi bi-arrow-up-circle-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-danger">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Total Pengeluaran</p>
                    <h5 class="fw-bold text-danger mb-0">Rp <?= number_format($totalExpense, 0, ',', '.') ?></h5>
                </div>
                <div class="p-3 rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width:46px;height:46px;">
                    <i class="bi bi-arrow-down-circle-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 <?= $balance >= 0 ? 'border-primary' : 'border-warning' ?>">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Saldo Kas Bersih</p>
                    <h5 class="fw-bold <?= $balance >= 0 ? 'text-primary' : 'text-warning' ?> mb-0">Rp <?= number_format($balance, 0, ',', '.') ?></h5>
                </div>
                <div class="p-3 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:46px;height:46px;">
                    <i class="bi bi-wallet2-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('keuangan/transaksi') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="No. Transaksi / Deskripsi..." value="<?= e($search ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Tipe</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="income" <?= ($selectedType ?? '') === 'income' ? 'selected' : '' ?>>Kas Masuk</option>
                    <option value="expense" <?= ($selectedType ?? '') === 'expense' ? 'selected' : '' ?>>Kas Keluar</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Dari</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate ?? '') ?>">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="<?= url('keuangan/transaksi') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small">Menampilkan <?= count($transactions) ?> dari <?= number_format($total) ?> transaksi</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            <p class="mb-0">Tidak ada transaksi yang ditemukan.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No. Transaksi</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th class="text-end">Nominal</th>
                        <th>Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td class="ps-3 font-monospace fw-semibold text-primary small"><?= e($t['transaction_number']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($t['transaction_date'])) ?></td>
                        <td>
                            <?php if ($t['type'] === 'income'): ?>
                                <span class="badge bg-success-subtle text-success border"><i class="bi bi-arrow-up me-1"></i>Masuk</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-arrow-down me-1"></i>Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($t['category_name']) ?></td>
                        <td class="text-truncate" style="max-width:200px;" title="<?= e($t['description']) ?>"><?= e($t['description']) ?></td>
                        <td class="text-end fw-bold <?= $t['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                            Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                        </td>
                        <td>
                            <?php
                            $stClass = match($t['status'] ?? 'active') {
                                'active' => 'bg-success',
                                'voided' => 'bg-danger',
                                default  => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $stClass ?>"><?= e($t['status'] ?? 'active') ?></span>
                        </td>
                        <td class="text-center pe-3">
                            <a href="<?= url('keuangan/transaksi/detail?id=' . (int)$t['id']) ?>" class="btn btn-outline-primary btn-sm py-0 px-2" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
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
                        <a class="page-link" href="<?= url('keuangan/transaksi?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
