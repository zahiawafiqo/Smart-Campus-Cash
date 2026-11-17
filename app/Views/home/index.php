<!-- Hero Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-primary-subtle text-primary small fw-semibold mb-3">
                    <i class="bi bi-stars"></i> Sistem Keuangan Kampus Berbasis Web & AI
                </div>
                <h1 class="display-5 fw-bold text-dark mb-3 lh-sm">
                    Kelola Kas & Keuangan Kampus Lebih Cepat, Transparan, & Cerdas.
                </h1>
                <p class="lead text-secondary mb-4">
                    <strong>Smart Campus Cash</strong> dirancang dengan arsitektur MVC modern untuk mengakomodasi seluruh stakeholder kampus &mdash; dari Admin, Mahasiswa, Kasir, hingga Bagian Keuangan dengan integrasi AI Financial Assistant & Insight.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= url('dashboard') ?>" class="btn btn-primary btn-lg px-4 py-2.5 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-speedometer2"></i> Masuk ke Dashboard Demo
                    </a>
                    <button type="button" onclick="document.getElementById('scChatbotLauncher')?.click()" class="btn btn-outline-primary btn-lg px-4 py-2.5 rounded-3 d-inline-flex align-items-center gap-2 shadow-xs">
                        <i class="bi bi-chat-dots-fill text-primary"></i> Tanya AI Chatbot
                    </button>
                    <a href="<?= url('components') ?>" class="btn btn-outline-secondary btn-lg px-4 py-2.5 rounded-3 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-puzzle"></i> UI Components
                    </a>
                </div>

                <!-- Database Status Badge -->
                <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2">
                    <span class="small text-muted">Status Koneksi MySQL (.env):</span>
                    <?php if (!empty($dbStatus['status'])): ?>
                        <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> <?= e($dbStatus['message']) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis px-2.5 py-1.5 rounded-pill">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Standby (DB belum dimigrasi / konfigurasi .env aktif)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-danger" style="width: 10px; height: 10px;"></div>
                            <div class="rounded-circle bg-warning" style="width: 10px; height: 10px;"></div>
                            <div class="rounded-circle bg-success" style="width: 10px; height: 10px;"></div>
                            <span class="small ms-2 text-white-50">Smart Campus Cash &bull; Preview</span>
                        </div>
                        <span class="badge bg-primary rounded-pill small">MVC Ready</span>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <div class="p-3 bg-white rounded-3 border mb-3 shadow-xs">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small text-muted fw-semibold">Saldo Kas Kampus (Placeholder)</span>
                                <span class="badge bg-success-subtle text-success">Aktif</span>
                            </div>
                            <h4 class="fw-bold text-dark mb-0">Rp 1.250.000.000</h4>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2.5 bg-white rounded-3 border">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Pemasukan SPP</small>
                                    <span class="fw-bold text-success">+ Rp 450.000.000</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 bg-white rounded-3 border">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Pengeluaran Kas</small>
                                    <span class="fw-bold text-danger">- Rp 120.000.000</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle" style="cursor: pointer;" onclick="document.getElementById('scChatbotLauncher')?.click()" title="Klik untuk membuka AI Chatbot">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-robot text-primary"></i>
                                    <span class="fw-bold text-primary small">AI Chatbot NVIDIA &bull; Node.js</span>
                                </div>
                                <span class="badge bg-primary rounded-pill" style="font-size: 0.65rem;">Coba Chat</span>
                            </div>
                            <p class="small text-secondary mb-0">
                                Asisten cerdas bertenaga NVIDIA NIM & server Node.js. Klik untuk berkonsultasi seputar keuangan kampus.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Role Selection Cards Section -->
<section class="py-5">
    <div class="container py-2">
        <div class="text-center max-w-xl mx-auto mb-5">
            <h2 class="fw-bold text-dark">4 Role Pengguna Terintegrasi</h2>
            <p class="text-secondary">Pilih salah satu peran di bawah ini untuk melihat simulasi navigasi dan tata letak dashboard:</p>
        </div>

        <div class="row g-4">
            <!-- Admin -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border rounded-3 card-hover">
                    <div class="card-body p-4 text-center">
                        <div class="p-3 rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Admin</h5>
                        <p class="text-muted small mb-3">Kontrol data mahasiswa, penetapan tarif tagihan, manajemen pengguna, dan hak akses.</p>
                        <a href="<?= url('dashboard?role=admin') ?>" class="btn btn-outline-danger btn-sm w-100 rounded-2">
                            Buka Dashboard <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mahasiswa -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border rounded-3 card-hover">
                    <div class="card-body p-4 text-center">
                        <div class="p-3 rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-mortarboard fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Mahasiswa</h5>
                        <p class="text-muted small mb-3">Cek tagihan semester, riwayat pembayaran, cetak bukti setor, dan konsultasi AI Assistant.</p>
                        <a href="<?= url('dashboard?role=mahasiswa') ?>" class="btn btn-outline-info btn-sm w-100 rounded-2">
                            Buka Dashboard <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Kasir -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border rounded-3 card-hover">
                    <div class="card-body p-4 text-center">
                        <div class="p-3 rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Kasir</h5>
                        <p class="text-muted small mb-3">Loket transaksi kas langsung, validasi pembayaran, cetak kuitansi resmi, dan rekap shift.</p>
                        <a href="<?= url('dashboard?role=kasir') ?>" class="btn btn-outline-warning btn-sm w-100 rounded-2">
                            Buka Dashboard <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bagian Keuangan -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border rounded-3 card-hover">
                    <div class="card-body p-4 text-center">
                        <div class="p-3 rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-bank fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Bagian Keuangan</h5>
                        <p class="text-muted small mb-3">Monitoring arus kas operasional, jurnal pemasukan/pengeluaran, dan laporan komprehensif.</p>
                        <a href="<?= url('dashboard?role=keuangan') ?>" class="btn btn-outline-success btn-sm w-100 rounded-2">
                            Buka Dashboard <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Include Floating AI Chatbot (Node.js & NVIDIA NIM) -->
<?php require __DIR__ . '/../components/home_chatbot.php'; ?>
