<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">Portal Keuangan Mahasiswa</h4>
            <span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1">Self-Service Portal</span>
        </div>
        <p class="text-secondary small mb-0">Informasi tagihan perkuliahan, riwayat transaksi pembayaran kas, dan bukti setor kuitansi resmi.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success-subtle text-success border p-2 rounded-2 small">
            <i class="bi bi-person-check-fill me-1"></i> Data Terisolasi (Milik Anda)
        </span>
    </div>
</div>

<!-- Student Identity Banner Card -->
<?php if ($student): ?>
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-primary text-white">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 60px; height: 60px;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h5 class="fw-bold mb-0 text-white"><?= e($student['full_name']) ?></h5>
                    <span class="badge bg-white text-primary rounded-pill px-2.5 py-1 small fw-bold font-monospace">NIM: <?= e($student['nim']) ?></span>
                </div>
                <div class="text-white-50 small d-flex flex-wrap gap-3">
                    <span><i class="bi bi-journal-code me-1"></i> <?= e($student['study_program']) ?></span>
                    <span><i class="bi bi-calendar3 me-1"></i> Angkatan <?= e($student['academic_year']) ?> (Semester <?= (int)$student['current_semester'] ?>)</span>
                    <span><i class="bi bi-people me-1"></i> Kelas <?= e($student['class_group']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <?php
    render_component('alert', [
        'type' => 'warning',
        'icon' => 'bi-exclamation-triangle-fill',
        'message' => 'Akun Anda belum ditautkan dengan data profil mahasiswa. Hubungi staf administrasi kampus.',
        'dismissible' => false,
        'class' => 'mb-4'
    ]);
    ?>
<?php endif; ?>

<!-- 3 Financial Metric Cards -->
<div class="row g-3 g-xl-4 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Total Tagihan Kuliah</span>
                    <h4 class="fw-bold text-dark mb-1">Rp <?= number_format($totalTagihan, 0, ',', '.') ?></h4>
                    <span class="text-muted small"><?= count($bills) ?> Pos Kewajiban</span>
                </div>
                <div class="p-3 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-credit-card-2-front fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Total Terbayar</span>
                    <h4 class="fw-bold text-success mb-1">Rp <?= number_format($totalTerbayar, 0, ',', '.') ?></h4>
                    <span class="text-success small"><i class="bi bi-check-circle me-1"></i>Tervalidasi Kasir</span>
                </div>
                <div class="p-3 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Sisa Tunggakan</span>
                    <h4 class="fw-bold <?= $sisaTunggakan > 0 ? 'text-danger' : 'text-success' ?> mb-1">
                        Rp <?= number_format($sisaTunggakan, 0, ',', '.') ?>
                    </h4>
                    <span class="<?= $sisaTunggakan > 0 ? 'text-danger' : 'text-success' ?> small">
                        <?= $sisaTunggakan > 0 ? 'Perlu Dilunasi' : 'Bebas Tanggungan' ?>
                    </span>
                </div>
                <div class="p-3 rounded-3 <?= $sisaTunggakan > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi <?= $sisaTunggakan > 0 ? 'bi-exclamation-octagon' : 'bi-shield-check' ?> fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bills Table Section -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <?php
        ob_start();
        $billCols = [
            ['key' => 'inv', 'label' => 'No. Invoice / Tagihan', 'raw' => true],
            ['key' => 'title', 'label' => 'Nama Tagihan', 'raw' => true],
            ['key' => 'category', 'label' => 'Kategori'],
            ['key' => 'nominal', 'label' => 'Nominal', 'raw' => true],
            ['key' => 'due', 'label' => 'Jatuh Tempo'],
            ['key' => 'status', 'label' => 'Status', 'raw' => true],
            ['key' => 'action', 'label' => 'Aksi', 'raw' => true, 'tdClass' => 'text-end'],
        ];

        $billRows = [];
        foreach ($bills as $b) {
            $statusBadge = match($b['status']) {
                'paid' => '<span class="badge bg-success-subtle text-success">Lunas</span>',
                'partially_paid' => '<span class="badge bg-warning-subtle text-warning">Sebagian</span>',
                'unpaid' => '<span class="badge bg-danger-subtle text-danger">Belum Bayar</span>',
                default => '<span class="badge bg-secondary-subtle text-secondary">' . e($b['status']) . '</span>'
            };

            $billRows[] = [
                'inv' => '<span class="font-monospace small fw-semibold">' . e($b['invoice_number']) . '</span>',
                'title' => '<span class="fw-bold text-dark">' . e($b['title']) . '</span>' . ($b['description'] ? '<small class="d-block text-muted">' . e($b['description']) . '</small>' : ''),
                'category' => e($b['category_name']),
                'nominal' => '<span class="fw-bold text-dark">Rp ' . number_format($b['amount'], 0, ',', '.') . '</span>' . ($b['paid_amount'] > 0 ? '<small class="d-block text-success">Dibayar: Rp ' . number_format($b['paid_amount'], 0, ',', '.') . '</small>' : ''),
                'due' => date('d M Y', strtotime($b['due_date'])),
                'status' => $statusBadge,
                'action' => '<button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2.5 rounded-2" onclick="showToast(\'Info Tagihan\', \'Invoice ' . e($b['invoice_number']) . ' dapat dibayar di loket kasir kampus.\', \'info\')"><i class="bi bi-info-circle me-1"></i>Cara Bayar</button>'
            ];
        }

        render_component('table', [
            'columns' => $billCols,
            'rows' => $billRows,
            'emptyMessage' => 'Tidak ada tagihan yang terdaftar untuk akun Anda.'
        ]);
        $billBody = ob_get_clean();

        render_component('card', [
            'title' => 'Daftar Tagihan Biaya Kuliah Anda',
            'subtitle' => 'Data disaring secara otomatis khusus untuk NIM ' . e($student['nim'] ?? '-'),
            'icon' => 'bi-receipt-cutoff',
            'badge' => count($bills) . ' Tagihan',
            'badgeVariant' => 'primary',
            'body' => $billBody
        ]);
        ?>
    </div>

    <!-- Payments History Table Section -->
    <div class="col-12">
        <?php
        ob_start();
        $payCols = [
            ['key' => 'pay_no', 'label' => 'No. Kuitansi Pembayaran', 'raw' => true],
            ['key' => 'bill', 'label' => 'Tagihan Terkait', 'raw' => true],
            ['key' => 'method', 'label' => 'Metode', 'raw' => true],
            ['key' => 'amount', 'label' => 'Nominal Bayar', 'raw' => true],
            ['key' => 'date', 'label' => 'Waktu Bayar'],
            ['key' => 'status', 'label' => 'Status', 'raw' => true],
            ['key' => 'action', 'label' => 'Bukti Setor', 'raw' => true, 'tdClass' => 'text-end'],
        ];

        $payRows = [];
        foreach ($payments as $p) {
            $payRows[] = [
                'pay_no' => '<span class="font-monospace small fw-semibold text-primary">' . e($p['payment_number']) . '</span>',
                'bill' => '<span class="fw-medium text-dark">' . e($p['bill_title'] ?? '-') . '</span>',
                'method' => '<span class="badge bg-light text-dark border text-uppercase small">' . e($p['payment_method']) . '</span>',
                'amount' => '<span class="fw-bold text-success">Rp ' . number_format($p['amount'], 0, ',', '.') . '</span>',
                'date' => date('d M Y, H:i', strtotime($p['payment_date'])),
                'status' => '<span class="badge bg-success-subtle text-success"><i class="bi bi-patch-check-fill me-1"></i>Terverifikasi</span>',
                'action' => '<button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 rounded-2" onclick="showToast(\'Kuitansi Digital\', \'Kuitansi resmi ' . e($p['payment_number']) . ' siap diunduh.\', \'success\')"><i class="bi bi-file-earmark-pdf me-1"></i>Unduh</button>'
            ];
        }

        render_component('table', [
            'columns' => $payCols,
            'rows' => $payRows,
            'emptyMessage' => 'Belum ada riwayat pembayaran yang tercatat.'
        ]);
        $payBody = ob_get_clean();

        render_component('card', [
            'title' => 'Riwayat Pembayaran Kas Mahasiswa',
            'subtitle' => 'Daftar penyetoran kas yang telah divalidasi oleh Kasir Kampus',
            'icon' => 'bi-clock-history',
            'badge' => count($payments) . ' Transaksi',
            'badgeVariant' => 'success',
            'body' => $payBody
        ]);
        ?>
    </div>
</div>
