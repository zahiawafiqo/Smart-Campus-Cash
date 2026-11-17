<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1">Audit Log Aktivitas Sistem</h4>
        <p class="text-secondary small mb-0">Catatan lengkap jejak aktivitas keamanan, autentikasi, transaksi finansial, dan query AI.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('admin/audit-logs') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari action, modul, username, atau IP..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="action" class="form-select form-select-sm">
                    <option value="">Semua Action</option>
                    <option value="LOGIN" <?= $selectedAction === 'LOGIN' ? 'selected' : '' ?>>LOGIN</option>
                    <option value="LOGOUT" <?= $selectedAction === 'LOGOUT' ? 'selected' : '' ?>>LOGOUT</option>
                    <option value="CREATE_USER" <?= $selectedAction === 'CREATE_USER' ? 'selected' : '' ?>>CREATE_USER</option>
                    <option value="CREATE_INVOICE" <?= $selectedAction === 'CREATE_INVOICE' ? 'selected' : '' ?>>CREATE_INVOICE</option>
                    <option value="PROCESS_PAYMENT" <?= $selectedAction === 'PROCESS_PAYMENT' ? 'selected' : '' ?>>PROCESS_PAYMENT</option>
                    <option value="VOID_PAYMENT" <?= $selectedAction === 'VOID_PAYMENT' ? 'selected' : '' ?>>VOID_PAYMENT</option>
                    <option value="CREATE_INCOME" <?= $selectedAction === 'CREATE_INCOME' ? 'selected' : '' ?>>CREATE_INCOME</option>
                    <option value="CREATE_EXPENSE" <?= $selectedAction === 'CREATE_EXPENSE' ? 'selected' : '' ?>>CREATE_EXPENSE</option>
                    <option value="AI_QUERY" <?= $selectedAction === 'AI_QUERY' ? 'selected' : '' ?>>AI_QUERY</option>
                    <option value="UNAUTHORIZED_ACCESS_ATTEMPT" <?= $selectedAction === 'UNAUTHORIZED_ACCESS_ATTEMPT' ? 'selected' : '' ?>>UNAUTHORIZED_ATTEMPT</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="<?= url('admin/audit-logs') ?>" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th style="width: 160px;">Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Modul / ID</th>
                    <th>Detail Nilai (Diff JSON)</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada catatan log audit yang sesuai kriteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="text-muted font-monospace"><?= date('d M Y H:i:s', strtotime($l['created_at'])) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($l['username'] ?? 'Guest/CLI') ?></div>
                                <?php if (!empty($l['full_name'])): ?>
                                    <small class="text-muted"><?= e($l['full_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match($l['action']) {
                                    'LOGIN' => 'bg-info-subtle text-info',
                                    'LOGOUT' => 'bg-secondary-subtle text-secondary',
                                    'PROCESS_PAYMENT', 'CREATE_INCOME' => 'bg-success-subtle text-success',
                                    'VOID_PAYMENT', 'UNAUTHORIZED_ACCESS_ATTEMPT' => 'bg-danger-subtle text-danger',
                                    'AI_QUERY' => 'bg-primary-subtle text-primary',
                                    default => 'bg-warning-subtle text-warning'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> font-monospace"><?= e($l['action']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($l['entity_type'] ?? '-') ?></span>
                                <?php if ($l['entity_id']): ?>
                                    <span class="font-monospace text-muted small">#<?= (int)$l['entity_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($l['new_values'])): ?>
                                    <code class="small text-truncate d-inline-block" style="max-width: 320px;" title="<?= e($l['new_values']) ?>">
                                        <?= e($l['new_values']) ?>
                                    </code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="font-monospace text-muted"><?= e($l['ip_address'] ?? '-') ?></td>
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
                'baseUrl' => url('admin/audit-logs?search=' . urlencode($search) . '&action=' . urlencode($selectedAction)),
                'totalItems' => $total,
                'perPage' => $perPage
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>
