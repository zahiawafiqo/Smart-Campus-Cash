<?php
$currentUser = \App\Services\AuthService::user();
$currentRole = \App\Services\AuthService::role();
?>
<nav class="navbar navbar-expand bg-white border-bottom shadow-xs sticky-top px-3 py-2">
    <div class="container-fluid p-0">
        <!-- Sidebar Toggle Button (Mobile & Desktop) -->
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-light btn-sm border-0 d-flex align-items-center justify-content-center p-2 rounded-2" id="sidebarToggleBtn" type="button" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Breadcrumb Context -->
            <div class="d-none d-md-flex align-items-center gap-2 ms-2">
                <span class="badge bg-primary-subtle text-primary fw-medium px-2 py-1 rounded-2">
                    <i class="bi bi-wallet2 me-1"></i> Smart Campus Cash
                </span>
                <i class="bi bi-chevron-right text-muted small" style="font-size: 0.7rem;"></i>
                <span class="text-secondary small fw-medium"><?= e($pageTitle ?? 'Dashboard') ?></span>
            </div>
        </div>

        <!-- Right Action Items -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <!-- Active Role Indicator -->
            <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-2 d-none d-sm-inline-flex align-items-center gap-1.5">
                <i class="bi bi-shield-lock-fill text-primary"></i>
                <span class="small fw-semibold text-capitalize"><?= e($currentRole ?? 'User') ?></span>
            </span>

            <!-- Notifications Indicator Placeholder -->
            <div class="dropdown">
                <button class="btn btn-light btn-sm border-0 position-relative p-2 rounded-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
                    <i class="bi bi-bell fs-6 text-secondary"></i>
                    <span class="position-absolute top-1 start-100 translate-middle p-1 bg-success border border-light rounded-circle">
                        <span class="visually-hidden">Sistem Aktif</span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3" style="width: 280px;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold fs-6">Notifikasi</h6>
                        <span class="badge bg-success-subtle text-success small">Online</span>
                    </div>
                    <div class="p-2 rounded-2 bg-light border small text-muted">
                        <i class="bi bi-check-circle text-success me-1"></i> Sesi Anda aktif sebagai <strong><?= e($currentRole) ?></strong> dengan otorisasi RBAC.
                    </div>
                </div>
            </div>

            <!-- Theme Mode Toggle Button -->
            <button class="btn btn-light btn-sm border-0 d-flex align-items-center justify-content-center p-2 rounded-2 theme-toggle-btn" id="themeToggleBtn" type="button" aria-label="Ganti ke Mode Gelap" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ganti ke Mode Gelap">
                <i class="bi bi-moon-stars-fill fs-6 text-secondary theme-icon-moon"></i>
                <i class="bi bi-sun-fill fs-6 text-warning theme-icon-sun d-none"></i>
            </button>

            <!-- User Avatar & Dropdown -->
            <div class="dropdown ms-1">
                <button class="btn btn-link p-0 text-decoration-none dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-xs" style="width: 34px; height: 34px; font-size: 0.85rem;">
                        <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 small">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold text-dark"><?= e($currentUser['full_name'] ?? 'Pengguna') ?></div>
                        <small class="text-muted"><?= e($currentUser['email'] ?? '') ?></small>
                    </li>
                    <li><a class="dropdown-item py-2" href="<?= \App\Services\AuthService::getRedirectForRole() ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard Saya</a></li>
                    <li><a class="dropdown-item py-2" href="<?= url('components') ?>"><i class="bi bi-puzzle me-2"></i>UI Components</a></li>
                    <li><button type="button" class="dropdown-item py-2 theme-toggle-btn"><i class="bi bi-circle-half me-2"></i><span class="theme-text">Ganti Mode Tema</span></button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
