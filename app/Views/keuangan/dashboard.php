<?php
// Fallback defensif untuk variabel data view
$transactions = (isset($transactions) && is_array($transactions)) 
    ? $transactions 
    : ((isset($recentTransactions) && is_array($recentTransactions)) ? $recentTransactions : []);

$categoriesSummary = (isset($categoriesSummary) && is_array($categoriesSummary)) ? $categoriesSummary : [];
$totalIncome = (float)($totalIncome ?? 0);
$totalExpense = (float)($totalExpense ?? 0);
$saldoKas = (float)($saldoKas ?? 0);

$totalTransactions = count($transactions);
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">Manajemen Kas &amp; Keuangan Kampus</h4>
            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">Otorisasi Finansial</span>
        </div>
        <p class="text-secondary small mb-0">Pengawasan arus kas masuk dan keluar (*Cash Flow*), pembukuan buku kas umum, dan laporan rekapitulasi.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('keuangan/laporan') ?>" class="btn btn-outline-secondary btn-sm rounded-2">
            <i class="bi bi-printer me-1"></i> Cetak Laporan Kas
        </a>
        <a href="<?= url('keuangan/transaksi/create') ?>" class="btn btn-success btn-sm rounded-2">
            <i class="bi bi-plus-circle me-1"></i> Catat Kas Masuk/Keluar
        </a>
    </div>
</div>

<!-- 3 Cash Metric Cards -->
<div class="row g-3 g-xl-4 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Total Pemasukan Kas (Income)</span>
                    <h4 class="fw-bold text-success mb-1">Rp <?= number_format($totalIncome, 0, ',', '.') ?></h4>
                    <span class="text-success small"><i class="bi bi-arrow-up-right me-1"></i>Penerimaan SPP &amp; DPP</span>
                </div>
                <div class="p-3 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Total Pengeluaran Kas (Expense)</span>
                    <h4 class="fw-bold text-danger mb-1">Rp <?= number_format($totalExpense, 0, ',', '.') ?></h4>
                    <span class="text-danger small"><i class="bi bi-arrow-down-right me-1"></i>Utilitas &amp; Operasional</span>
                </div>
                <div class="p-3 rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-graph-down-arrow fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Saldo Kas Bersih Tersedia</span>
                    <h4 class="fw-bold text-primary mb-1">Rp <?= number_format($saldoKas, 0, ',', '.') ?></h4>
                    <span class="text-primary small"><i class="bi bi-wallet2 me-1"></i>Likuiditas Kampus</span>
                </div>
                <div class="p-3 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-bank fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- General Ledger (Buku Kas Umum) -->
    <div class="col-lg-8">
        <?php
        ob_start();
        $txCols = [
            ['key' => 'tx_no', 'label' => 'No. Transaksi / Waktu', 'raw' => true],
            ['key' => 'category', 'label' => 'Pos Anggaran', 'raw' => true],
            ['key' => 'type_badge', 'label' => 'Tipe', 'raw' => true],
            ['key' => 'amount_val', 'label' => 'Nominal', 'raw' => true],
            ['key' => 'creator', 'label' => 'Petugas'],
        ];

        $txRows = [];
        if (!empty($transactions)) {
            foreach ($transactions as $t) {
                $isIncome = (($t['type'] ?? '') === 'income');
                $txDate = !empty($t['transaction_date']) ? date('d M Y H:i', strtotime($t['transaction_date'])) : '-';
                $txNumber = e($t['transaction_number'] ?? '-');
                $catName = e($t['category_name'] ?? 'Umum');
                $desc = e($t['description'] ?? '-');
                $amount = (float)($t['amount'] ?? 0);
                $creator = e($t['creator_name'] ?? '-');

                $txRows[] = [
                    'tx_no' => '<span class="font-monospace small fw-bold text-dark">' . $txNumber . '</span><small class="d-block text-muted">' . $txDate . '</small>',
                    'category' => '<span class="fw-semibold text-dark">' . $catName . '</span><small class="d-block text-secondary text-truncate" style="max-width: 200px;">' . $desc . '</small>',
                    'type_badge' => $isIncome 
                        ? '<span class="badge bg-success-subtle text-success"><i class="bi bi-arrow-down-left me-1"></i>Masuk</span>' 
                        : '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-arrow-up-right me-1"></i>Keluar</span>',
                    'amount_val' => '<span class="fw-bold ' . ($isIncome ? 'text-success' : 'text-danger') . '">' . ($isIncome ? '+ ' : '- ') . 'Rp ' . number_format($amount, 0, ',', '.') . '</span>',
                    'creator' => '<span class="small text-muted">' . $creator . '</span>'
                ];
            }
        }

        render_component('table', [
            'columns' => $txCols,
            'rows' => $txRows,
            'emptyMessage' => 'Belum ada transaksi mutasi buku kas tercatat.'
        ]);
        $txBody = ob_get_clean();

        $badgeText = ($totalTransactions > 0) ? ($totalTransactions . ' Mutasi') : '0 Transaksi';
        $badgeVariant = ($totalTransactions > 0) ? 'primary' : 'secondary';

        render_component('card', [
            'title' => 'Buku Kas Umum (General Ledger)',
            'subtitle' => 'Pencatatan real-time arus kas masuk dan kas keluar',
            'icon' => 'bi-journal-bookmark-fill',
            'badge' => $badgeText,
            'badgeVariant' => $badgeVariant,
            'body' => $txBody
        ]);
        ?>
    </div>

    <!-- Category Budget Breakdown -->
    <div class="col-lg-4">
        <?php
        ob_start();
        ?>
        <div class="d-flex flex-column gap-2.5">
            <?php if (empty($categoriesSummary)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-pie-chart fs-3 d-block mb-1 opacity-50"></i>
                    <span class="small">Belum ada data pos anggaran tercatat.</span>
                </div>
            <?php else: ?>
                <?php foreach ($categoriesSummary as $cs): ?>
                    <?php $isInc = (($cs['type'] ?? '') === 'income'); ?>
                    <div class="p-2.5 rounded-2 bg-light border d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge <?= $isInc ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> mb-1" style="font-size: 0.65rem;">
                                <?= $isInc ? 'Pemasukan' : 'Pengeluaran' ?>
                            </span>
                            <h6 class="fw-semibold text-dark mb-0 small"><?= e($cs['name'] ?? '-') ?></h6>
                            <small class="text-muted font-monospace" style="font-size: 0.7rem;"><?= e($cs['code'] ?? '-') ?></small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold <?= $isInc ? 'text-success' : 'text-danger' ?> d-block small">
                                Rp <?= number_format((float)($cs['total_amount'] ?? 0), 0, ',', '.') ?>
                            </span>
                            <small class="text-muted" style="font-size: 0.7rem;"><?= (int)($cs['total_tx'] ?? 0) ?> transaksi</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        $catBody = ob_get_clean();

        render_component('card', [
            'title' => 'Pos Anggaran Kategori',
            'subtitle' => 'Rekapitulasi per pos pemasukan dan pengeluaran',
            'icon' => 'bi-pie-chart-fill',
            'body' => $catBody
        ]);
        ?>
    </div>
</div>

