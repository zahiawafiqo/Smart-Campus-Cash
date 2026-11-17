<?php
// View: kasir/kuitansi.php - rendered inside layouts/app
$appName = env('APP_NAME', 'Smart Campus Cash');
?>
<style>
@media print {
    .no-print { display: none !important; }
    .kuitansi-wrapper { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>

<div class="no-print d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-receipt text-primary me-2"></i>Kuitansi Pembayaran</h4>
        <p class="text-muted small mb-0">Dokumen resmi bukti penerimaan pembayaran dari mahasiswa.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary rounded-2">
            <i class="bi bi-printer me-1"></i> Cetak Kuitansi
        </button>
        <a href="<?= url('kasir/riwayat') ?>" class="btn btn-outline-secondary rounded-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-12 col-md-9 col-lg-7">
<div class="card border-0 shadow-sm rounded-3 kuitansi-wrapper" style="font-family:'Segoe UI',Arial,sans-serif;">
    <div class="card-body p-4 p-md-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom border-2">
            <div>
                <h5 class="fw-bold text-primary mb-0"><?= e($appName) ?></h5>
                <small class="text-muted">Sistem Kas Keuangan Kampus Cerdas</small>
            </div>
            <div class="text-end">
                <div class="fw-bold text-dark" style="font-size:1.1rem;">KUITANSI RESMI</div>
                <div class="font-monospace small text-primary"><?= e($payment['payment_number']) ?></div>
            </div>
        </div>

        <!-- Meta info -->
        <div class="bg-primary-subtle rounded-2 p-3 mb-4">
            <div class="row g-1 small">
                <div class="col-4 col-md-auto me-md-4">
                    <span class="text-muted d-block">Tanggal</span>
                    <strong><?= date('d F Y, H:i', strtotime($payment['payment_date'])) ?> WIB</strong>
                </div>
                <div class="col-4 col-md-auto me-md-4">
                    <span class="text-muted d-block">Kasir</span>
                    <strong><?= e($payment['cashier_name'] ?? 'Kasir Loket') ?></strong>
                </div>
                <div class="col-4 col-md-auto">
                    <span class="text-muted d-block">Status</span>
                    <span class="badge bg-success">LUNAS</span>
                </div>
            </div>
        </div>

        <!-- Data Mahasiswa -->
        <h6 class="fw-bold text-muted text-uppercase small mb-2">Data Mahasiswa</h6>
        <table class="table table-sm small mb-4">
            <tr>
                <td class="text-muted" style="width:40%;">Nama</td>
                <td class="fw-semibold"><?= e($payment['student_name']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">NIM</td>
                <td class="font-monospace fw-semibold"><?= e($payment['nim']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Program Studi</td>
                <td><?= e($payment['study_program'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="text-muted">Fakultas</td>
                <td><?= e($payment['faculty'] ?? '-') ?></td>
            </tr>
        </table>

        <!-- Data Tagihan -->
        <h6 class="fw-bold text-muted text-uppercase small mb-2">Data Tagihan & Pembayaran</h6>
        <table class="table table-sm small mb-4">
            <tr>
                <td class="text-muted" style="width:40%;">No. Invoice</td>
                <td class="font-monospace text-primary"><?= e($payment['invoice_number'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="text-muted">Judul Tagihan</td>
                <td><?= e($payment['bill_title'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="text-muted">Kategori</td>
                <td><?= e($payment['category_name'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="text-muted">Total Tagihan</td>
                <td>Rp <?= number_format($payment['bill_total_amount'] ?? 0, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td class="text-muted">Metode Bayar</td>
                <td><span class="badge bg-secondary"><?= strtoupper(e($payment['payment_method'])) ?></span></td>
            </tr>
            <?php if (!empty($payment['notes'])): ?>
            <tr>
                <td class="text-muted">Catatan</td>
                <td><?= e($payment['notes']) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <!-- Nominal Box -->
        <div class="border border-2 border-success rounded-3 p-3 text-center mb-4 bg-success-subtle">
            <div class="small text-muted mb-1">JUMLAH YANG DITERIMA</div>
            <div class="fw-bold text-success" style="font-size:2rem;">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></div>
            <div class="small text-muted mt-1">
                Sisa tagihan: <strong>Rp <?= number_format($payment['bill_remaining'] ?? 0, 0, ',', '.') ?></strong>
                &bull; Status: 
                <?php
                $stClass = match($payment['bill_status'] ?? '') {
                    'PAID'    => 'bg-success',
                    'PARTIAL' => 'bg-warning text-dark',
                    'OVERDUE' => 'bg-danger',
                    default   => 'bg-secondary'
                };
                ?>
                <span class="badge <?= $stClass ?>"><?= e($payment['bill_status'] ?? '-') ?></span>
            </div>
        </div>

        <!-- Tanda Tangan -->
        <div class="d-flex justify-content-end mb-4">
            <div class="text-center">
                <p class="small mb-5">Petugas Kasir,</p>
                <div style="border-top:1px solid #374151;width:180px;display:inline-block;margin-bottom:4px;"></div>
                <p class="fw-semibold small mb-0"><?= e($payment['cashier_name'] ?? 'Kasir Loket') ?></p>
                <p class="text-muted small"><?= e($appName) ?></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-top pt-3">
            <p class="text-muted mb-0" style="font-size:0.75rem;">
                Kuitansi ini diterbitkan secara resmi oleh sistem <?= e($appName) ?>. 
                No. Kuitansi: <strong><?= e($payment['payment_number']) ?></strong>.
                Dicetak pada: <?= date('d F Y, H:i:s') ?> WIB.
            </p>
        </div>
    </div>
</div>
</div>
</div>
