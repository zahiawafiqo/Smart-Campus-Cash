<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">Operasional Loket Kasir</h4>
            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1">Loket 01 Aktif</span>
        </div>
        <p class="text-secondary small mb-0">Pencarian data mahasiswa, validasi penerimaan kas, dan pencetakan kuitansi resmi.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success-subtle text-success border p-2 rounded-2 small">
            <i class="bi bi-cash-coin me-1"></i> Kasir: <?= e($user['full_name']) ?>
        </span>
    </div>
</div>

<!-- Search Mahasiswa Section -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-search me-1 text-primary"></i> Cari Mahasiswa Pembayar</h6>
        <form action="<?= url('kasir/dashboard') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-bounding-box"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Ketik NIM (contoh: 2110114001) atau Nama Mahasiswa..." value="<?= e($search) ?>" autofocus>
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 rounded-2">
                    <i class="bi bi-search me-1"></i> Cari Data
                </button>
            </div>
        </form>

        <?php if (!empty($search)): ?>
            <div class="mt-4 pt-3 border-top">
                <h6 class="small fw-bold text-muted text-uppercase mb-3">Hasil Pencarian untuk: "<?= e($search) ?>" (<?= count($searchResults) ?> ditemukan)</h6>
                <?php if (empty($searchResults)): ?>
                    <p class="text-muted small mb-0">Tidak ada mahasiswa yang cocok dengan kata kunci pencarian tersebut.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($searchResults as $m): ?>
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 border bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0"><?= e($m['full_name']) ?></h6>
                                        <span class="text-muted small font-monospace d-block">NIM: <?= e($m['nim']) ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary small"><?= e($m['study_program']) ?></span>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger-subtle text-danger mb-2 d-block"><?= (int)$m['unpaid_bills_count'] ?> Tagihan Aktif</span>
                                        <button type="button" class="btn btn-sm btn-primary py-1 px-2.5 rounded-2" onclick="openPaymentModal('<?= e($m['nim']) ?>', '<?= e($m['full_name']) ?>')">
                                            <i class="bi bi-credit-card me-1"></i> Bayar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 2 Metric Cards & Security Reminder -->
<div class="row g-3 g-xl-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Penerimaan Kasir Hari Ini</span>
                    <h4 class="fw-bold text-success mb-1">Rp <?= number_format($todayTotal, 0, ',', '.') ?></h4>
                    <span class="text-muted small">Shift <?= e($user['username']) ?></span>
                </div>
                <div class="p-3 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 card-hover">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium d-block mb-1">Tagihan Belum Lunas</span>
                    <h4 class="fw-bold text-warning-emphasis mb-1"><?= count($pendingBills) ?> Tagihan</h4>
                    <span class="text-muted small">Menunggu Pembayaran</span>
                </div>
                <div class="p-3 rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-hourglass-top fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-light-subtle border-start border-primary border-3">
            <div class="card-body p-3.5">
                <span class="small fw-bold text-primary d-block mb-1"><i class="bi bi-shield-lock me-1"></i> Batasan Izin Kasir (RBAC)</span>
                <p class="small text-muted mb-0" style="font-size: 0.8rem;">
                    Role Kasir hanya diotorisasi melayani loket dan memproses pembayaran. Akses ke konfigurasi sistem, database, dan jurnal pembukuan diblokir oleh backend.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Pending Bills Table -->
    <div class="col-lg-7">
        <?php
        ob_start();
        $billCols = [
            ['key' => 'student', 'label' => 'Mahasiswa', 'raw' => true],
            ['key' => 'inv', 'label' => 'No. Invoice / Judul', 'raw' => true],
            ['key' => 'remaining', 'label' => 'Sisa Tagihan', 'raw' => true],
            ['key' => 'action', 'label' => 'Loket', 'raw' => true, 'tdClass' => 'text-end'],
        ];

        $billRows = [];
        foreach ($pendingBills as $pb) {
            $billRows[] = [
                'student' => '<span class="fw-bold text-dark d-block">' . e($pb['student_name']) . '</span><small class="text-muted font-monospace">' . e($pb['nim']) . '</small>',
                'inv' => '<span class="font-monospace small fw-semibold text-primary">' . e($pb['invoice_number']) . '</span><small class="d-block text-secondary">' . e($pb['title']) . '</small>',
                'remaining' => '<span class="fw-bold text-danger">Rp ' . number_format($pb['remaining_amount'], 0, ',', '.') . '</span>',
                'action' => '<button type="button" class="btn btn-sm btn-success py-0.5 px-2.5 rounded-2" onclick="openPaymentModal(\'' . e($pb['nim']) . '\', \'' . e($pb['student_name']) . '\', \'' . e($pb['invoice_number']) . '\', ' . (float)$pb['remaining_amount'] . ')"><i class="bi bi-wallet2 me-1"></i>Proses</button>'
            ];
        }

        render_component('table', [
            'columns' => $billCols,
            'rows' => $billRows,
            'emptyMessage' => 'Tidak ada tagihan tertunggak saat ini.'
        ]);
        $pbBody = ob_get_clean();

        render_component('card', [
            'title' => 'Daftar Tagihan Siap Dibayar',
            'subtitle' => 'Antrian tagihan mahasiswa untuk diproses di loket',
            'icon' => 'bi-receipt',
            'badge' => count($pendingBills) . ' Antrian',
            'badgeVariant' => 'warning',
            'body' => $pbBody
        ]);
        ?>
    </div>

    <!-- Recent Payments Recorded -->
    <div class="col-lg-5">
        <?php
        ob_start();
        $rpCols = [
            ['key' => 'pay_no', 'label' => 'No. Kuitansi', 'raw' => true],
            ['key' => 'student', 'label' => 'Mahasiswa', 'raw' => true],
            ['key' => 'amount', 'label' => 'Nominal', 'raw' => true],
        ];

        $rpRows = [];
        foreach ($recentPayments as $rp) {
            $rpRows[] = [
                'pay_no' => '<span class="font-monospace small fw-semibold text-primary">' . e($rp['payment_number']) . '</span><small class="d-block text-muted">' . date('d M H:i', strtotime($rp['payment_date'])) . '</small>',
                'student' => '<span class="fw-medium text-dark">' . e($rp['student_name']) . '</span><span class="badge bg-light text-dark border ms-1">' . strtoupper($rp['payment_method']) . '</span>',
                'amount' => '<span class="fw-bold text-success">Rp ' . number_format($rp['amount'], 0, ',', '.') . '</span>'
            ];
        }

        render_component('table', [
            'columns' => $rpCols,
            'rows' => $rpRows,
            'emptyMessage' => 'Belum ada transaksi pembayaran yang diproses pada shift ini.'
        ]);
        $rpBody = ob_get_clean();

        render_component('card', [
            'title' => 'Kuitansi Diproses Kasir Ini',
            'subtitle' => 'Rekapitulasi setoran kasir Anda hari ini',
            'icon' => 'bi-printer',
            'badge' => count($recentPayments) . ' Terbit',
            'badgeVariant' => 'success',
            'body' => $rpBody
        ]);
        ?>
    </div>
</div>

<!-- Modal Dialog Proses Pembayaran Loket -->
<div class="modal fade" id="paymentProcessModal" tabindex="-1" aria-labelledby="paymentProcessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fs-6 fw-bold" id="paymentProcessModalLabel"><i class="bi bi-wallet2 text-success me-1"></i> Loket Penerimaan Kas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Mahasiswa:</span>
                        <strong id="mModalName" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">NIM:</span>
                        <span id="mModalNim" class="font-monospace text-dark">-</span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Invoice:</span>
                        <span id="mModalInv" class="font-monospace text-primary">-</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nominal Pembayaran (Rp)</label>
                    <input type="number" id="mModalAmount" class="form-control fw-bold fs-5 text-success" value="0">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Metode Pembayaran</label>
                    <select class="form-select" id="mModalMethod">
                        <option value="cash" selected>Tunai (Cash Loket)</option>
                        <option value="qris">QRIS Standar</option>
                        <option value="bank_transfer">Transfer Bank</option>
                        <option value="edc">Kartu Debit / EDC</option>
                    </select>
                </div>

                <div class="alert alert-info small py-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i> Kuitansi cetak otomatis dan mutasi buku kas akan terbentuk saat pembayaran divalidasi.
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success btn-sm px-3" onclick="confirmPaymentProcess()">
                    <i class="bi bi-check2-circle me-1"></i> Simpan &amp; Cetak Kuitansi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openPaymentModal(nim, name, inv = 'PILIH TAGIHAN', amount = 0) {
    document.getElementById('mModalNim').innerText = nim;
    document.getElementById('mModalName').innerText = name;
    document.getElementById('mModalInv').innerText = inv;
    document.getElementById('mModalAmount').value = amount;
    
    const modalEl = document.getElementById('paymentProcessModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function confirmPaymentProcess() {
    const modalEl = document.getElementById('paymentProcessModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    showToast('Pembayaran Divalidasi', 'Setoran kas berhasil dicatat dan kuitansi telah siap dicetak.', 'success');
}
</script>
