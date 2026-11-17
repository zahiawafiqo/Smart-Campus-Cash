<?php
$currentRole = strtolower(trim(\App\Services\AuthService::role() ?? 'admin'));
$currentUser = \App\Services\AuthService::user();
$currentUri = \App\Core\App::getCurrentUri();


// Definisi menu navigasi per role
$menus = [
    'admin' => [
        'label' => 'Administrator',
        'badge' => 'bg-danger-subtle text-danger',
        'icon' => 'bi-shield-check',
        'dashboardUrl' => url('admin/dashboard'),
        'items' => [
            ['title' => 'Dashboard Utama', 'icon' => 'bi-speedometer2', 'url' => url('admin/dashboard')],
            ['title' => 'Data Mahasiswa', 'icon' => 'bi-people', 'url' => url('admin/students'), 'badge' => 'Master'],
            ['title' => 'Kategori Transaksi', 'icon' => 'bi-tags', 'url' => url('admin/categories')],
            ['title' => 'Master Tagihan & SPP', 'icon' => 'bi-receipt-cutoff', 'url' => url('admin/bills')],
            ['title' => 'Manajemen Pengguna', 'icon' => 'bi-person-gear', 'url' => url('admin/users')],
            ['title' => 'Notifikasi WhatsApp', 'icon' => 'bi-whatsapp', 'url' => url('notifications'), 'badge' => 'WA'],
            ['title' => 'Pengaturan Sistem', 'icon' => 'bi-sliders2', 'url' => url('admin/settings')],
            ['title' => 'Audit Log Aktivitas', 'icon' => 'bi-clock-history', 'url' => url('admin/audit-logs')],
            ['title' => 'AI Financial Assistant', 'icon' => 'bi-stars', 'url' => url('ai-assistant'), 'badge' => 'AI'],
        ]
    ],
    'mahasiswa' => [
        'label' => 'Mahasiswa',
        'badge' => 'bg-info-subtle text-info',
        'icon' => 'bi-mortarboard',
        'dashboardUrl' => url('mahasiswa/dashboard'),
        'items' => [
            ['title' => 'Portal Mahasiswa', 'icon' => 'bi-grid-1x2', 'url' => url('mahasiswa/dashboard')],
            ['title' => 'Tagihan Kuliah Saya', 'icon' => 'bi-credit-card-2-front', 'url' => url('mahasiswa/tagihan'), 'badge' => 'Aktif'],
            ['title' => 'Riwayat Pembayaran', 'icon' => 'bi-clock-history', 'url' => url('mahasiswa/riwayat')],
            ['title' => 'AI Financial Assistant', 'icon' => 'bi-stars', 'url' => url('ai-assistant'), 'badge' => 'AI'],
        ]
    ],
    'kasir' => [
        'label' => 'Kasir Loket',
        'badge' => 'bg-warning-subtle text-warning',
        'icon' => 'bi-cash-coin',
        'dashboardUrl' => url('kasir/dashboard'),
        'items' => [
            ['title' => 'Dashboard Kasir', 'icon' => 'bi-hdd-rack', 'url' => url('kasir/dashboard')],
            ['title' => 'Loket Pembayaran', 'icon' => 'bi-upc-scan', 'url' => url('kasir/pembayaran'), 'badge' => 'Loket 01'],
            ['title' => 'Riwayat Pembayaran', 'icon' => 'bi-receipt', 'url' => url('kasir/riwayat')],
            ['title' => 'Notifikasi WhatsApp', 'icon' => 'bi-whatsapp', 'url' => url('notifications'), 'badge' => 'WA'],
            ['title' => 'AI Financial Assistant', 'icon' => 'bi-stars', 'url' => url('ai-assistant'), 'badge' => 'AI'],
        ]
    ],
    'keuangan' => [
        'label' => 'Bagian Keuangan',
        'badge' => 'bg-success-subtle text-success',
        'icon' => 'bi-bank',
        'dashboardUrl' => url('keuangan/dashboard'),
        'items' => [
            ['title' => 'Dashboard Keuangan', 'icon' => 'bi-graph-up-arrow', 'url' => url('keuangan/dashboard')],
            ['title' => 'Buku Kas Umum', 'icon' => 'bi-cash-stack', 'url' => url('keuangan/transaksi'), 'badge' => 'Kas'],
            ['title' => 'Master Tagihan', 'icon' => 'bi-receipt-cutoff', 'url' => url('admin/bills')],
            ['title' => 'Kategori Transaksi', 'icon' => 'bi-tags', 'url' => url('admin/categories')],
            ['title' => 'Laporan Rekapitulasi', 'icon' => 'bi-file-earmark-bar-graph', 'url' => url('keuangan/laporan')],
            ['title' => 'Notifikasi WhatsApp', 'icon' => 'bi-whatsapp', 'url' => url('notifications'), 'badge' => 'WA'],
            ['title' => 'AI Financial Assistant', 'icon' => 'bi-stars', 'url' => url('ai-assistant'), 'badge' => 'AI'],
        ]
    ],
];

$roleData = $menus[$currentRole] ?? $menus['admin'];
?>

<aside class="sidebar bg-white border-end shadow-sm d-flex flex-column" id="appSidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand px-3 py-3 border-bottom d-flex align-items-center justify-content-between">
        <a href="<?= $roleData['dashboardUrl'] ?>" class="text-decoration-none d-flex align-items-center gap-2">
            <div class="brand-icon bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
            <div>
                <span class="fw-bold fs-6 text-dark tracking-tight d-block lh-1">Campus Cash</span>
                <span class="text-primary small fw-semibold" style="font-size: 0.72rem;">SMART FINANCIAL</span>
            </div>
        </a>
        <button class="btn btn-sm btn-light d-lg-none" id="sidebarCloseBtn" type="button" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Active User & Role Badge -->
    <div class="px-3 pt-3 pb-2">
        <div class="p-2.5 rounded-3 bg-light border">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-muted small" style="font-size: 0.7rem;">Role Terotentikasi</span>
                <span class="badge <?= $roleData['badge'] ?> rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                    <i class="bi <?= $roleData['icon'] ?> me-1"></i><?= $roleData['label'] ?>
                </span>
            </div>
            <div class="fw-bold text-dark text-truncate small">
                <?= e($currentUser['full_name'] ?? 'User') ?>
            </div>
            <div class="text-muted small font-monospace" style="font-size: 0.72rem;">
                @<?= e($currentUser['username'] ?? '-') ?>
            </div>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <div class="sidebar-nav flex-grow-1 overflow-y-auto px-2 py-2">
        <div class="nav-section-title px-2.5 pt-2 pb-1 text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">
            Menu Utama
        </div>
        <ul class="nav flex-column gap-1">
            <?php foreach ($roleData['items'] as $item): ?>
                <?php 
                    $itemPath = parse_url($item['url'], PHP_URL_PATH);
                    $isActive = ($item['url'] !== '#' && (
                        $currentUri === $itemPath ||
                        ($item['url'] === $roleData['dashboardUrl'] && str_ends_with($currentUri, 'dashboard')) ||
                        (!str_ends_with($item['url'], 'dashboard') && str_starts_with($currentUri, $itemPath))
                    ));
                ?>
                <li class="nav-item">
                    <a href="<?= $item['url'] ?>" class="nav-link d-flex align-items-center justify-content-between px-2.5 py-2 rounded-2 <?= $isActive ? 'active bg-primary text-white shadow-xs' : 'text-secondary hover-bg-light' ?>">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi <?= $item['icon'] ?> <?= $isActive ? 'text-white' : 'text-secondary' ?> fs-6"></i>
                            <span class="small fw-medium"><?= e($item['title']) ?></span>
                        </div>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="badge <?= $isActive ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' ?> rounded-pill" style="font-size: 0.65rem;">
                                <?= e($item['badge']) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Reusable Component Showcase Menu -->
        <div class="nav-section-title px-2.5 pt-3 pb-1 text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">
            Sistem & Navigasi
        </div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a href="<?= url('components') ?>" class="nav-link d-flex align-items-center gap-2 px-2.5 py-2 rounded-2 text-secondary hover-bg-light">
                    <i class="bi bi-puzzle fs-6"></i>
                    <span class="small fw-medium">UI Components</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('') ?>" class="nav-link d-flex align-items-center gap-2 px-2.5 py-2 rounded-2 text-secondary hover-bg-light">
                    <i class="bi bi-house fs-6"></i>
                    <span class="small fw-medium">Landing Page</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer p-3 border-top bg-light-subtle d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold small" style="width: 32px; height: 32px;">
                <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-info lh-1">
                <span class="fw-semibold text-dark small d-block"><?= e($roleData['label']) ?></span>
                <span class="text-success small" style="font-size: 0.7rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>Online</span>
            </div>
        </div>
        <a href="<?= url('logout') ?>" class="btn btn-sm btn-outline-danger p-1 px-2" title="Keluar / Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</aside>
