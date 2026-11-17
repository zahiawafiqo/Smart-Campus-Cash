<!-- Dashboard Top Header Section -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0"><?= e($roleMeta['title']) ?></h4>
            <span class="badge bg-<?= e($roleMeta['badgeColor']) ?>-subtle text-<?= e($roleMeta['badgeColor']) ?> rounded-pill px-2.5 py-1">
                <?= e($roleMeta['badge']) ?>
            </span>
        </div>
        <p class="text-secondary small mb-0"><?= e($roleMeta['subtitle']) ?></p>
    </div>

    <!-- Quick Action / Role Switcher Tool -->
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('components') ?>" class="btn btn-outline-primary btn-sm rounded-2 d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-puzzle"></i> Showcase Komponen
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-2 d-inline-flex align-items-center gap-1.5" onclick="showToast('Sistem Siap', 'Fondasi MVC dan layout aktif. Modul transaksi siap dikembangkan pada tahap selanjutnya.', 'primary')">
            <i class="bi bi-info-circle"></i> Info Status
        </button>
    </div>
</div>

<!-- Alert Notifikasi Placeholder -->
<?php
render_component('alert', [
    'type' => 'primary',
    'title' => 'Tahap Inisialisasi Fondasi MVC Berhasil!',
    'message' => 'Halaman ini merupakan <strong>template dashboard kosong</strong> yang responsif dengan navigasi role-based placeholder untuk <code>' . ucfirst($activeRole) . '</code>. Logika transaksi keuangan, AI Assistant, dan data MySQL akan diimplementasikan pada tahap selanjutnya.',
    'dismissible' => true,
    'class' => 'mb-4'
]);
?>

<!-- Placeholder Metric Summary Cards (4 Cards) -->
<div class="row g-3 g-xl-4 mb-4">
    <?php
    $metricCards = [
        'admin' => [
            ['title' => 'Total Mahasiswa Terdaftar', 'val' => '2.450', 'icon' => 'bi-people-fill', 'bg' => 'bg-primary-subtle text-primary', 'sub' => 'Data terintegrasi'],
            ['title' => 'Tagihan Aktif Terbit', 'val' => '1.820', 'icon' => 'bi-receipt', 'bg' => 'bg-info-subtle text-info', 'sub' => 'Semester Gasal'],
            ['title' => 'Pengguna Sistem', 'val' => '18 User', 'icon' => 'bi-person-gear', 'bg' => 'bg-warning-subtle text-warning', 'sub' => '4 Role aktif'],
            ['title' => 'Status Server & DB', 'val' => 'Normal', 'icon' => 'bi-database-check', 'bg' => 'bg-success-subtle text-success', 'sub' => 'MySQL Connected'],
        ],
        'mahasiswa' => [
            ['title' => 'Status Pembayaran Kuliah', 'val' => 'Lunas', 'icon' => 'bi-patch-check-fill', 'bg' => 'bg-success-subtle text-success', 'sub' => 'Semester Gasal 2026/2027'],
            ['title' => 'Sisa Tagihan Anda', 'val' => 'Rp 0', 'icon' => 'bi-wallet2', 'bg' => 'bg-info-subtle text-info', 'sub' => 'Tidak ada tunggakan'],
            ['title' => 'Riwayat Transaksi', 'val' => '6 Kali', 'icon' => 'bi-clock-history', 'bg' => 'bg-primary-subtle text-primary', 'sub' => 'Kuitansi terverifikasi'],
            ['title' => 'AI Financial Assistant', 'val' => 'Standby', 'icon' => 'bi-stars', 'bg' => 'bg-purple-subtle text-primary', 'sub' => 'Siap diintegrasikan'],
        ],
        'kasir' => [
            ['title' => 'Total Penerimaan Hari Ini', 'val' => 'Rp 48.500.000', 'icon' => 'bi-cash-coin', 'bg' => 'bg-success-subtle text-success', 'sub' => 'Shift 1 & 2'],
            ['title' => 'Transaksi Kasir Selesai', 'val' => '34 Mahasiswa', 'icon' => 'bi-check-circle', 'bg' => 'bg-primary-subtle text-primary', 'sub' => 'Cetak kuitansi selesai'],
            ['title' => 'Antrian Pembayaran Loket', 'val' => '3 Antrian', 'icon' => 'bi-hourglass-split', 'bg' => 'bg-warning-subtle text-warning', 'sub' => 'Loket A1'],
            ['title' => 'Status Tutup Kasir', 'val' => 'Buka', 'icon' => 'bi-door-open', 'bg' => 'bg-info-subtle text-info', 'sub' => 'Operasional aktif'],
        ],
        'keuangan' => [
            ['title' => 'Total Saldo Kas Kampus', 'val' => 'Rp 1.450.000.000', 'icon' => 'bi-bank', 'bg' => 'bg-success-subtle text-success', 'sub' => 'Kas utama & operasional'],
            ['title' => 'Pemasukan Kas Bulan Ini', 'val' => 'Rp 580.000.000', 'icon' => 'bi-graph-up-arrow', 'bg' => 'bg-primary-subtle text-primary', 'sub' => 'SPP & Biaya Praktikum'],
            ['title' => 'Pengeluaran Operasional', 'val' => 'Rp 125.000.000', 'icon' => 'bi-graph-down-arrow', 'bg' => 'bg-danger-subtle text-danger', 'sub' => 'Gaji & Operasional'],
            ['title' => 'AI Financial Insight', 'val' => 'Siap Analisis', 'icon' => 'bi-cpu', 'bg' => 'bg-info-subtle text-info', 'sub' => 'Model prediksi siap'],
        ],
    ];

    $cards = $metricCards[$activeRole] ?? $metricCards['admin'];
    foreach ($cards as $item):
    ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium d-block mb-1"><?= e($item['title']) ?></span>
                        <h4 class="fw-bold text-dark mb-1"><?= e($item['val']) ?></h4>
                        <span class="text-secondary" style="font-size: 0.75rem;"><?= e($item['sub']) ?></span>
                    </div>
                    <div class="p-3 rounded-3 <?= e($item['bg']) ?> d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi <?= e($item['icon']) ?> fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Empty Dashboard Canvas / Placeholder Section -->
<div class="row g-4">
    <div class="col-lg-8">
        <?php
        ob_start();
        ?>
            <div class="text-center py-5 px-3">
                <div class="mb-3">
                    <div class="p-3 rounded-circle bg-light d-inline-flex align-items-center justify-content-center border" style="width: 80px; height: 80px;">
                        <i class="bi bi-bar-chart-line text-primary fs-1 opacity-75"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Canvas Modul <?= e($roleMeta['title']) ?></h5>
                <p class="text-muted small max-w-md mx-auto mb-4" style="max-width: 480px;">
                    Area ini disiapkan untuk grafik visualisasi arus kas, tabel rincian transaksi data riil, dan widget operasional keuangan yang akan dibangun pada prompt tahap selanjutnya.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= url('components') ?>" class="btn btn-sm btn-primary px-3 rounded-2">
                        <i class="bi bi-puzzle me-1"></i> Buka Katalog UI Components
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#demoModal">
                        <i class="bi bi-window me-1"></i> Buka Modal Demo
                    </button>
                </div>
            </div>
        <?php
        $emptyBody = ob_get_clean();

        render_component('card', [
            'title' => 'Monitoring & Data Canvas',
            'subtitle' => 'Placeholder modul visualisasi data keuangan',
            'icon' => 'bi-grid',
            'badge' => 'Standby',
            'badgeVariant' => 'secondary',
            'body' => $emptyBody,
            'class' => 'h-100'
        ]);
        ?>
    </div>

    <div class="col-lg-4">
        <?php
        ob_start();
        ?>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                <li class="d-flex align-items-start gap-2.5 p-2.5 rounded-2 bg-light border">
                    <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                    <div class="small">
                        <span class="fw-bold text-dark d-block">Pola MVC Diterapkan</span>
                        <span class="text-muted">Pemisahan Controller, Model, View, Router & Helper telah tuntas.</span>
                    </div>
                </li>
                <li class="d-flex align-items-start gap-2.5 p-2.5 rounded-2 bg-light border">
                    <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                    <div class="small">
                        <span class="fw-bold text-dark d-block">Master Layout Responsive</span>
                        <span class="text-muted">Sidebar collapsible, navbar sticky, dan footer siap pakai.</span>
                    </div>
                </li>
                <li class="d-flex align-items-start gap-2.5 p-2.5 rounded-2 bg-light border">
                    <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                    <div class="small">
                        <span class="fw-bold text-dark d-block">7 UI Reusable Components</span>
                        <span class="text-muted">Button, Form, Table, Card, Modal, Alert, & Pagination siap pakai.</span>
                    </div>
                </li>
                <li class="d-flex align-items-start gap-2.5 p-2.5 rounded-2 bg-primary-subtle border border-primary-subtle">
                    <i class="bi bi-hourglass-split text-primary mt-0.5"></i>
                    <div class="small">
                        <span class="fw-bold text-primary d-block">Tahap Berikutnya</span>
                        <span class="text-secondary">Otentikasi multi-role, skema tabel MySQL, modul kasir & AI.</span>
                    </div>
                </li>
            </ul>
        <?php
        $sideBody = ob_get_clean();

        render_component('card', [
            'title' => 'Cek Status Arsitektur',
            'subtitle' => 'Pemeriksaan fondasi sistem',
            'icon' => 'bi-shield-check',
            'iconBg' => 'bg-success-subtle text-success',
            'body' => $sideBody,
            'class' => 'h-100'
        ]);
        ?>
    </div>
</div>

<!-- Modal Demo Component -->
<?php
ob_start();
?>
    <p class="text-secondary mb-2">Ini adalah komponen dialog modal reusable yang dirancang menggunakan Bootstrap 5.3.</p>
    <div class="p-3 bg-light rounded-2 border small text-muted mb-0">
        <i class="bi bi-lightbulb text-warning me-1"></i> Komponen ini dapat dipanggil di view manapun menggunakan fungsi <code>render_component('modal', [...])</code>.
    </div>
<?php
$modalBody = ob_get_clean();

render_component('modal', [
    'id' => 'demoModal',
    'title' => 'Komponen Modal Reusable',
    'body' => $modalBody,
    'size' => 'md'
]);
?>
