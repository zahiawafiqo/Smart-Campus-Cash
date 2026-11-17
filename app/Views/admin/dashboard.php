<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Dashboard Administrator</h4>
        <p class="text-secondary small mb-0">Ringkasan statistik operasional, pengguna sistem, dan kontrol penuh Smart Campus Cash.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/users/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-person-plus me-1"></i> Tambah User
        </a>
        <a href="<?= url('admin/bills/create') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-circle me-1"></i> Terbitkan Tagihan
        </a>
    </div>
</div>

<!-- 5 Metric Cards -->
<div class="row g-3 g-xl-4 mb-4">
    <!-- Total Mahasiswa -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Total Mahasiswa</span>
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-2"><i class="bi bi-people fs-6"></i></span>
                </div>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($stats['total_students']) ?></h3>
                <small class="text-muted">Mahasiswa Aktif Terdaftar</small>
            </div>
        </div>
    </div>

    <!-- Total User -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Pengguna Sistem</span>
                    <span class="badge bg-info-subtle text-info p-2 rounded-2"><i class="bi bi-person-badge fs-6"></i></span>
                </div>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($stats['total_users']) ?></h3>
                <small class="text-muted">Akun Multi-Role</small>
            </div>
        </div>
    </div>

    <!-- Total Transaksi -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Total Transaksi</span>
                    <span class="badge bg-warning-subtle text-warning p-2 rounded-2"><i class="bi bi-receipt fs-6"></i></span>
                </div>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($stats['total_transactions']) ?></h3>
                <small class="text-muted">Buku Kas &amp; Pembayaran</small>
            </div>
        </div>
    </div>

    <!-- Total Pemasukan -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Total Pemasukan</span>
                    <span class="badge bg-success-subtle text-success p-2 rounded-2"><i class="bi bi-arrow-down-left fs-6"></i></span>
                </div>
                <h4 class="fw-bold text-success mb-0">Rp <?= number_format($stats['total_income'], 0, ',', '.') ?></h4>
                <small class="text-muted">Akumulasi Kas Masuk</small>
            </div>
        </div>
    </div>

    <!-- Total Pengeluaran -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-medium">Total Pengeluaran</span>
                    <span class="badge bg-danger-subtle text-danger p-2 rounded-2"><i class="bi bi-arrow-up-right fs-6"></i></span>
                </div>
                <h4 class="fw-bold text-danger mb-0">Rp <?= number_format($stats['total_expense'], 0, ',', '.') ?></h4>
                <small class="text-muted">Akumulasi Kas Keluar</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Pengguna Sistem Terbaru -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-people text-primary fs-5"></i>
                    <h6 class="fw-bold mb-0">Pengguna Sistem Terdaftar</h6>
                </div>
                <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-light">Kelola Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data user.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($u['full_name']) ?></div>
                                        <small class="text-muted font-monospace">@<?= e($u['username']) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary"><?= e($u['role_name']) ?></span></td>
                                    <td>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted">
                                        <?= !empty($u['last_login_at']) ? date('d M Y H:i', strtotime($u['last_login_at'])) : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Audit Log Terbaru -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <h6 class="fw-bold mb-0">Audit Log Keamanan Terbaru</h6>
                </div>
                <a href="<?= url('admin/audit-logs') ?>" class="btn btn-sm btn-light">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php if (empty($auditLogs)): ?>
                        <li class="list-group-item text-center text-muted py-3">Belum ada catatan log audit.</li>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $log): ?>
                            <li class="list-group-item py-2.5 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.68rem;"><?= e($log['action']) ?></span>
                                    <small class="text-muted"><?= date('d M H:i', strtotime($log['created_at'])) ?></small>
                                </div>
                                <div class="text-dark fw-medium"><?= e($log['username'] ?? 'System') ?></div>
                                <small class="text-muted font-monospace">IP: <?= e($log['ip_address'] ?? '127.0.0.1') ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
